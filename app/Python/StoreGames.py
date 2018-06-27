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


def get_games_recursive(offset):
    games_request = get_games(offset)
    if games_request:
        for game in games_request['data']:
            allGames.append(game)
        if games_request['pagination']['size'] == 1000:
            print(offset)
            get_games_recursive(offset + 1000)


def get_games(offset):
    url = 'https://www.speedrun.com/api/v1/games'
    params = dict(_bulk='yes', max=1000, offset=offset)
    request = requests.get(url, params)
    if request.status_code == 200:
        parsed_json = json.loads(request.text)
        return parsed_json
    else:
        return False


def get_records(game, offset):
    url = 'https://www.speedrun.com/api/v1/games/' + game['id'] + '/records'
    params = dict(top=2000, embed='players', max=200, offset=offset)
    request = requests.get(url, params)
    if request.status_code == 200:
        parsed_json = json.loads(request.text)
        if parsed_json['pagination']['size'] == 200:
            get_records(game, offset + parsed_json['pagination']['size'])
        for record in parsed_json['data']:
            valid = valid_video_id(record)
            if valid:
                store_record(record, valid)

        return parsed_json['data']
    else:
        return None


def store_all_game_records(game):
    get_records(game, 0)


def valid_video_id(record):
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


def store_record(record, valid_video_id):
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
        competition = len(record['players']['data'])
    except:
        competition = 0

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
        else:
            date_obj = None

    except Exception as error:
        print(repr(error))
        date_obj = None

    if valid_video_id['site'] == 'twitch':
        twitchId = valid_video_id['id']
        youtubeId = None
    else:
        twitchId = None
        youtubeId = valid_video_id['id']

    conn = pymysql.connect(host=dbHost, user=dbUsername, passwd=dbPassword, db=dbName, charset='utf8mb4')
    sql = "INSERT INTO `" + table_name + "` (`runId`, `gameId`, `categoryId`, `primaryTime`, `youtubeId`, `twitchId`, `levelId`, `platformId`, `regionId`, `date`, `competition`) VALUES(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)"
    cursor = conn.cursor()
    cursor.execute(sql, (recordId, gameId, categoryId, primaryTime, youtubeId, twitchId, levelId, platform, region, date_obj, competition))
    conn.commit()
    conn.close()

def create_new_table():

    conn = pymysql.connect(host=dbHost, user=dbUsername, passwd=dbPassword, db=dbName, charset='utf8mb4')
    sql = "CREATE TABLE `"+ table_name +"` LIKE `records`"
    cursor = conn.cursor()
    cursor.execute(sql)
    conn.commit()
    conn.close()

def update_active_table() :
    conn = pymysql.connect(host=dbHost, user=dbUsername, passwd=dbPassword, db=dbName, charset='utf8mb4')
    sql = "insert into `active_records` (`table_name`, `created_at`, `updated_at`) values(%s, CURRENT_TIMESTAMP(), CURRENT_TIMESTAMP())"
    cursor = conn.cursor()
    cursor.execute(sql, (table_name))
    conn.commit()
    conn.close()




start = time.time()

table_name = 'records_' + str(math.floor(start))

create_new_table()
allGames = []
get_games_recursive(0)
rate_limit = RatedSemaphore(99, 60)
for index, game in enumerate(allGames):
    with rate_limit:
        print('Elapsed Time: ', math.floor(time.time()-start), 's  #', index, game['id'])
        get_records(game, 0)

update_active_table()




print('it took ', time.time()-start, ' seconds')






