<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    function __construct( array $attributes = []) {
	    parent::__construct( $attributes );
    }

    public function setFromSpeedrun($gameObject) {
    	if(isset($gameObject->names->international)) {
    		$this->name = $gameObject->names->international;
	    }
	    if(isset($gameObject->id)){
    		$this->speedrunId = $gameObject->id;
	    }
	    if(isset($gameObject->{'release-date'})) {
	    	$this->releaseDate = $gameObject->{'release-date'};
	    }
	    if(isset($gameObject->weblink)) {
		    $this->weblink = $gameObject->weblink;
	    }
	    if(isset($gameObject->links)) {
	    	foreach($gameObject->links as $link){
	    		if($link->rel == 'records') {
	    			$this->records = $link->uri;
	    			break;
			    }
		    }
	    }
	    return true;
    }

}
