import json

import time
import requests
import re
import math
from datetime import datetime

from multiprocessing.dummy import Pool
from threading import BoundedSemaphore, Timer
import configparser
import pymysql


class RatedSemaphore(BoundedSemaphore):
    """Limit to 1 request per `period / value` seconds (over long run)."""
    def __init__(self, value=1, period=1):
        BoundedSemaphore.__init__(self, value)
        t = Timer(period, self._add_token_loop,
                  kwargs=dict(time_delta=float(period) / value))
        t.daemon = True
        t.start()

    def _add_token_loop(self, time_delta):
        """Add token every time_delta seconds."""
        while True:
            try:
                BoundedSemaphore.release(self)
            except ValueError: # ignore if already max possible value
                pass
            time.sleep(time_delta) # ignore EINTR

    def release(self):
        pass # do nothing (only time-based release() is allowed)


# CONFIG
config = configparser.ConfigParser()
config.read('config.ini')
dbHost = config.get('configuration', 'dbHost')
dbUsername = config.get('configuration', 'dbUsername')
dbPassword = config.get('configuration', 'dbPassword')
dbName = config.get('configuration', 'dbname')


# Lets start parsing through API requests and making some inserts


def GetGamesRecursive(offset):
    gamesRequest = GetGames(offset)
    if gamesRequest:
        for game in gamesRequest['data']:
            allGames.append(game)
        if gamesRequest['pagination']['size'] == 1000:
            print(offset)
            GetGamesRecursive(offset + 1000)




# def StoreSingleGame(game):


def GetGames(offset):
    url = 'https://www.speedrun.com/api/v1/games'
    params = dict(_bulk='yes', max=1000, offset=offset)
    request = requests.get(url, params)
    if request.status_code == 200:
        parsedJson = json.loads(request.text)
        return parsedJson
    else:
        return False

def GetRecords(game, offset):
    url = 'https://www.speedrun.com/api/v1/games/' + game['id'] + '/records'
    params = dict(top=1, embed='players', max=200, offset=offset)
    request = requests.get(url, params)
    if request.status_code == 200:
        parsedJson = json.loads(request.text)
        if parsedJson['pagination']['size'] == 200:
            GetRecords(game, offset + parsedJson['pagination']['size'])
        #print(parsedJson['pagination']['size'])

        for record in parsedJson['data']:
            valid = validVideoId(record)
            if valid:
                storeRecord(record, valid)
            #print(record)

        return parsedJson['data']
    else:
        return None

def StoreAllGames(allGamesClean):
    conn = pymysql.connect(host=dbHost, user=dbUsername, passwd=dbPassword, db=dbName, charset='utf8mb4')
    sql = "INSERT INTO `games` (`name`, `speedrunId`) VALUES(%s, %s)"
    cursor = conn.cursor()
    cursor.executemany(sql, allGamesClean)
    conn.commit()
    conn.close()

def StoreAllGameRecords(game):
    GetRecords(game, 0)



def validVideoId(record):
    #print('record: ', record)
    try:
        uri = record['runs'][0]['run']['videos']['links'][0]['uri']

        if 'twitch' in uri:

            twitchShort = re.search(
                '(v).(\d{6,})',
                uri)
            if twitchShort:
                videoId = re.sub('[^0-9]', '', twitchShort.group(0))
                return dict(site='twitch', id=videoId)

            twitchLong = re.search(
                '(videos).(\d{6,})',
                uri)
            if twitchLong:
                videoId = re.sub('[^0-9]', '', twitchLong.group(0))
                return dict(site='twitch', id=videoId)

        else:
            youtube = re.search(
                r'(https?://)?(www\.)?' '(youtube|youtu|youtube-nocookie)\.(com|be)/' '(watch\?.*?(?=v=)v=|embed/|v/|.+\?v=)?([^&=%\?]{11})',
                uri)
            # print(youtube)
            if youtube:
                youtubeId = re.search(r'((?<=(v|V)/)|(?<=be/)|(?<=(\?|\&)v=)|(?<=embed/))([\w-]+)', uri)
                return dict(site='youtube', id=youtubeId.group(0))

        print('invalid video id', record['runs'][0]['run']['videos']['links'][0]['uri'])
        return False;
    except:
        #print('didnt have a run or video', record['weblink'])
        return False


def storeRecord(record, validVideoId):
    #print('storing record', record)
    try:
        recordId = record['runs'][0]['run']['id']
    except:
        recordId = None
    try:
        gameId = record['runs'][0]['run']['game']
    except:
        gameId = None
    try:
        categoryId = record['runs'][0]['run']['category']
    except:
        categoryId = None
    try:
        platform = record['runs'][0]['run']['system']['platform']
    except:
        platform = None
    try:
        region = record['runs'][0]['run']['system']['region']
    except:
        region = None
    try:
        primaryTime = record['runs'][0]['run']['times']['primary_t']
    except:
        primaryTime = None

    try:
        levelId = record['level']
    except:
        levelId = None


    try:
        if record['runs'][0]['run']['date'] is not None:
            date = record['runs'][0]['run']['date']
            date_obj = datetime.strptime(date, '%Y-%m-%d')
        elif record['runs'][0]['run']['submitted'] is not None:
            date = record['runs'][0]['run']['submitted']
            date_obj = datetime.strptime(date, '%Y-%m-%dT%H:%M:%SZ')

    except Exception as error:
        print(repr(error))
        date_obj = None

    if validVideoId['site'] == 'twitch':
        twitchId = validVideoId['id']
        youtubeId = None
    else:
        twitchId = None
        youtubeId = validVideoId['id']

    conn = pymysql.connect(host=dbHost, user=dbUsername, passwd=dbPassword, db=dbName, charset='utf8mb4')
    sql = "INSERT INTO `records` (`runId`, `gameId`, `categoryId`, `primaryTime`, `youtubeId`, `twitchId`, `levelId`, `platformId`, `regionId`, `date`) VALUES(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s)"
    cursor = conn.cursor()
    cursor.execute(sql, (recordId, gameId, categoryId, primaryTime, youtubeId, twitchId, levelId, platform, region, date_obj))
    conn.commit()
    conn.close()


start = time.time()

allGames = []
GetGamesRecursive(0)

rate_limit = RatedSemaphore(99, 60)

for index, game in enumerate(allGames):
    with rate_limit:
        print('Elapsed Time: ', math.floor(time.time()-start), 's  #', index, game['id'])
        GetRecords(game, 0)

print('it took ', time.time()-start, ' seconds')

print(allGames[0]['id'])






