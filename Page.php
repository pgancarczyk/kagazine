<?php

class Page {
	
	public static function getLoggedPlayer() {
		$dbTemp = new PDO('mysql:host=localhost;dbname=kag', 'kag', 'FeEQPyh88CyzcCq5GRqP');
		$dbTemp->query("SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8'");
		if (isset($_COOKIE['playerID']) and isset($_COOKIE['hash'])) {
			$playerID = intval($_COOKIE['playerID']);
			$hash = preg_replace("/[^a-zA-Z0-9]+/", "", $_COOKIE['hash']);
			$row = $dbTemp->query("select hash from auth where playerID = ".$playerID)->fetch();
			if($row) {
				if ($row['hash'] == $hash) {
					$player = Player::createByID($playerID, $dbTemp);
					$dbTemp->query("insert into websiteActivity ( playerID ) values ( ".$player->ID." ) on duplicate key update seen = now()");
					$dbTemp = null;
					return $player;
				}
			}
		}
		$dbTemp = null;
		return false;
	}
	
	public static function curlGet($url, $final=false) {
		static $c = null;
		if(!$c) $c = curl_init();
		curl_setopt($c, CURLOPT_URL, $url);
		curl_setopt($c, CURLOPT_HEADER, 0);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 1);
		curl_setopt($c, CURLOPT_TIMEOUT, 2);
		curl_setopt($c, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
		$content = curl_exec($c);
		if($final)	curl_close($c);
		return $content;

	}
	
	public static function printHeader($title, $relativePath = "", $includeMap = false) {
		if(isset($_GET['body'])) {
			if ($_GET['body'] === 'only') {
				echo '<div id=dataDiv data-title="'.$title.'"></div>'."\n";
				return true;
			}
		}
		//if ($relativePath == "../") $relativePath = ""; // bo nie ma domeny
		echo "<!doctype html>\n";
		echo '<meta charset="utf-8">'."\n";
		echo '<meta name=viewport content="width=device-width,initial-scale=1">'."\n";
		echo '<link rel=stylesheet href='.$relativePath."default.css?dupa=chujjjj>\n";
		echo '<link rel=stylesheet href="//fonts.googleapis.com/css?family=Open+Sans">';
		echo '<script src="https://code.jquery.com/jquery-2.1.4.min.js"></script>'."\n";
		echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-timeago/1.5.2/jquery.timeago.min.js"></script>'."\n";
		echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/jQuery-linkify/1.1.7/jquery.linkify.min.js"></script>'."\n";
		echo '<script src="https://code.jquery.com/ui/1.12.0-rc.2/jquery-ui.min.js"></script>'."\n";
		echo '<script src="https://cdn.jsdelivr.net/jquery.marquee/1.3.9/jquery.marquee.min.js"></script>'."\n";
		echo '<script src="'.$relativePath.'playSound.js?dupa=chuj"></script>'."\n";
		echo '<script src="'.$relativePath.'chart.js"></script>'."\n";
		echo '<script src="'.$relativePath.'jquery.emoji.js?dupa=chuj"></script>'."\n";
		echo '<script src="'.$relativePath.'jquery.flags.js?dupa=chuj"></script>'."\n";
		// if ($includeMap) {
			echo '<link rel=stylesheet href="'.$relativePath.'jquery-jvectormap-2.0.2.css">'."\n";
			echo '<script src="'.$relativePath.'jquery-jvectormap-2.0.2.min.js"></script>'."\n";
			echo '<script src="'.$relativePath.'world.js"></script>'."\n"; 
		// }
		echo "<title>".$title."</title>\n";
		echo "<div id=header>\n";
		echo "<div id=title><h1><span class=title><a onclick=".'"mainPage(event)"'." href=".$relativePath.".>KAGAZINE</a></span></h1>\n";
		$player = self::getLoggedPlayer();
		if ($player) echo '<h1><a onclick="mePage(event)" href="'.$relativePath.'me"><span class=login>logged as '.$player->name.'</span></a></h1>'."\n";
		else echo '<h1><span class=login><a href="'.$relativePath.'login"'.">Connect your account</a></span></h1>\n";
		echo '</div></div>'."\n";
		echo "<div id=body>\n";
		if($player) {
			$dbTemp = new PDO('mysql:host=localhost;dbname=kag', 'kag', 'FeEQPyh88CyzcCq5GRqP');
			$dbTemp->query("SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8'");
			$result = $dbTemp->query('select playerID from websiteActivity where seen > date_sub(now(), interval 10 second)');
			$list = '';
			$count = 0;
			while($row = $result->fetch()) {
				$list.= Player::createByID($row['playerID'], $dbTemp)->getA().", ";
				$count++;
			}
			if ($list != '') echo "<p id=visiting>Visiting the site: ".substr($list, 0, -2)." (".$count." player".($count > 1 ? "s" : "").").</p><div id=bodyContent>\n";
			else echo "<p=visiting>Visiting the site: ".$player->getA()." (1 player).</p>";
		}
        // echo '<p class="pName">Check out friends feature! Visit a profile and add it to your list so you get notified when they join KAG.<br>As for other updates, /skip now works properly and there is a 10 minutes limit on youtube videos :)</p>';
		echo '<div id=bodyContent>';
	}
	
	public static function printFooter() {
		if(isset($_GET['body'])) {
			if ($_GET['body'] === 'only') return true;
		}
		echo "</div></div>";
		echo "<div style='text-align: center; margin: 20px 0 -30px 0; padding-bottom: 10px; font-weight: 400; font-size: 18px; font-family: Arial'>ｓｔａｌｋｉｎｇ　ｓｉｎｃｅ　２０１６</div>";
		$player = self::getLoggedPlayer();
	if(!$player) echo "<style>#input { display: none; } #messages {height: 367px;}</style>";
?>
<div id=shoutbox>
<div id=videoArea><div id=videoContainer><iframe id="videoObject" type="text/html" width="400" height="300" frameborder="0"></iframe></div></div>
<div id=sidebar class=radius><span id=videoInfo><span id=videoTitle></span></span><span id=aContainerSidebar class=radius><span id=videoTime><br></span><a href=# id=toggleNotif onClick="toggleNotif(event)"><img class="emoji" width="20" height="20" align="absmiddle" src="https://github.com/SCRAPTURE/jquery-emoji/raw/master/images/emojis/exclamation.png" alt="exclamation" title="mute notifications"></a><a href=# id=toggleSound onClick="toggleSound(event)"><img class="emoji" width="20" height="20" align="absmiddle" src="https://github.com/SCRAPTURE/jquery-emoji/raw/master/images/emojis/speaker.png" alt="speaker" title="mute"></a><a href=# id=toggleVideo onClick="toggleVideo(event)"><img class="emoji" width="20" height="20" align="absmiddle" src="https://github.com/SCRAPTURE/jquery-emoji/raw/master/images/emojis/tv.png" alt="arrow_down_small" title="show video"></a><a href=# id=toggleChat  onclick="toggleChat(event)"><img class="emoji" width="20" height="20" align="absmiddle" src="https://github.com/SCRAPTURE/jquery-emoji/raw/master/images/emojis/arrow_down_small.png" alt="arrow_down_small" title="hide chat"></a></span></div>
<div id=content>
<div id=messages>
</div>
<input type=text id=input maxlength=512 placeholder="type here">
</div>
</div>
<div style="display: none">
<img alt src="https://github.com/SCRAPTURE/jquery-emoji/raw/master/images/emojis/grey_exclamation.png">
<img alt src="https://github.com/SCRAPTURE/jquery-emoji/raw/master/images/emojis/musical_note.png">
<img alt src="https://github.com/SCRAPTURE/jquery-emoji/raw/master/images/emojis/arrow_up_small.png">
<img alt src="https://github.com/SCRAPTURE/jquery-emoji/raw/master/images/emojis/mute.png">
<div>
<script>
var jsTime = Math.floor(Date.now()/1000);
var phpTime = <?php echo time(); ?>;
var timeDifference = phpTime - jsTime;
var toggle = true;
var sidebar = $("#sidebar");
var content = $("#content");
var messages = $("#messages");
var input = $("#input");
var shoutbox = $("#shoutbox")
var visiting = $("#visiting");
//$("#header").draggable().resizable();
oldVisiting = visiting.html();
lastMsgID = 0;
firstUpdateDone = false;
mute = false;
doDing = false;
secs = false;
var showVideo = false;
var doNotif = true;
var isLogged = <?php if ($player) echo 'true'; else echo 'false'; ?>;
var myName = '<?php if ($player) echo $player->name; else echo ''; ?>';
var queue = [];
var ytPlaying = false;
var nowPlayingNameA = '';
var firstFriendsUpdate = false;
<?php if ($player) {
    echo 'var friends = {';
	$dbTemp = new PDO('mysql:host=localhost;dbname=kag', 'kag', 'FeEQPyh88CyzcCq5GRqP');
	$dbTemp->query("SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8'");
    $result = $dbTemp->query('select friendID from friends where playerID = '.$player->ID);
    while ($row = $result->fetch()) {
        echo "'".Player::CreateByID($row['friendID'], $dbTemp)->name."': false,";
    }
    echo "};\n";
?>
if (!jQuery.isEmptyObject(friends)) {
    window.setInterval(function(){
        $.ajax({
                type: "GET",
                url: "https://api.kag2d.com/servers/gid/0/current/1/empty/0/connectable/1",
                success: function(data){
                    var friendsToCheck = Object.create(friends);
                    for (player in friendsToCheck) {
                       friendsToCheck[player] = false; 
                    }
                    data['serverList'].forEach(function(server) {
                        server['playerList'].forEach(function(player) {
                            if(friends.hasOwnProperty(player)) {
                                friendsToCheck[player] = true;
                                if (!friends[player]) {
                                    if(firstFriendsUpdate) notify(player + ' has just joined ' + server['serverName']);
                                    friends[player] = true;
                                    // console.log('no kurwa');
                                }
                            }
                        });
                    });
                    for (player in friendsToCheck) {
                       if(!friendsToCheck[player]) friends[player] = false; 
                    }
                firstFriendsUpdate = true;
                }
        });
        $.ajax({
                type: "GET",
                url: "https://api.kag2d.com/v1/game/thd/kag/servers?filters=[{%E2%80%9Cfield%E2%80%9D:%E2%80%9CcurrentPlayers%E2%80%9D,%E2%80%9Cop%E2%80%9D:%E2%80%9Cgt%E2%80%9D,%E2%80%9Cvalue%E2%80%9D:%220%22},{%E2%80%9Cfield%E2%80%9D:%E2%80%9Ccurrent%E2%80%9D,%E2%80%9Cop%E2%80%9D:%E2%80%9Ceq%E2%80%9D,%E2%80%9Cvalue%E2%80%9D:%22true%22}]",
                success: function(data){
					var ilenabecie = 0;
                    data['serverList'].forEach(function(server) {
                        server['playerList'].forEach(function(player) {
									ilenabecie++;
                                });
                            });
						$("#betacounter").html("In comparision, " + ilenabecie + " people are playing the release version.");
				}						
        });
    }, 5000);
}
function addToFriends(e) {
    e.preventDefault();
    $.ajax({
        type: "POST",
        url: "http://37.187.176.227/kag/friend.php",
        data: { friendName: $("#addFriend").data('name') },
        success: function(data) {
            $("#addFriend").html(data);
            friends[$("#addFriend").data('name')] = false;
        }
    });   
}
function removeFriend(e) {
    e.preventDefault();
    $.ajax({
        type: "POST",
        url: "http://37.187.176.227/kag/unfriend.php",
        data: { friendName: $("#addFriend").data('name') },
        success: function(data) {
            $("#addFriend").html(data);
            delete friends[$("#addFriend").data('name')];
        }
    });   
}
<?php } ?>
function isMeNext() {
	if (queue[1] == myName) return true;
  else return false;
}
function removeFromQueue(name) {
	if (typeof name === 'string') queue.splice(queue.indexOf(name), 1);
	else queue.shift();
}
function addToQueue(name) {
	queue.push(name);
}
function timer(sec_num) {
    var hours   = Math.floor(sec_num / 3600);
    var minutes = Math.floor((sec_num - (hours * 3600)) / 60);
    var seconds = sec_num - (hours * 3600) - (minutes * 60);
	if (seconds < 10) seconds = '0' + seconds;
    if (hours > 0) return hours+':'+minutes+':'+seconds;
    else return minutes+':'+seconds;
}
var firstYoutube = false;
 var tag = document.createElement('script');
  tag.src = "https://www.youtube.com/player_api";
  var firstScriptTag = document.getElementsByTagName('script')[0];
  firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
function onYouTubeIframeAPIReady() {
	ytPlayer = new YT.Player('videoObject', { playerVars: { origin: "http://37.187.176.227/kag", widget_referrer: "http://37.187.176.227/kag" }, events: { 'onStateChange': onStateChange}});
	//console.log("player zaladowany, " + ytPlayer.getVideoData().title);
	console.log(ytPlayer);
	//ytPlayer.playVideo();
}
function toggleNotif(e) {
	if(doNotif) {
		doNotif = false;
		$("#toggleNotif").html('<img class="emoji" width="20" height="20" align="absmiddle" src="https://github.com/SCRAPTURE/jquery-emoji/raw/master/images/emojis/grey_exclamation.png" alt="grey_exclamation" title="show notifications">');
        localStorage.setItem("toggleNotif", true);
	}
	else {
		doNotif = true;
		$("#toggleNotif").html('<img class="emoji" width="20" height="20" align="absmiddle" src="https://github.com/SCRAPTURE/jquery-emoji/raw/master/images/emojis/exclamation.png" alt="exclamation" title="don\'t show notifications">');
        localStorage.setItem("toggleNotif", false);
	}
	if (e) e.preventDefault();
}
function toggleVideo(e) {
	if(showVideo) {
		$("#videoArea").hide();
		showVideo = false;
		sidebar.addClass("radius");
		$("aContainerSidebar").addClass("radius");
		$("#toggleVideo").html('<img class="emoji" width="20" height="20" align="absmiddle" src="https://github.com/SCRAPTURE/jquery-emoji/raw/master/images/emojis/tv.png" alt="tv" title="show video">');
        localStorage.setItem("toggleVideo", false);
	}
	else {
		$("#videoArea").show();;
		showVideo = true;
		sidebar.removeClass("radius");
		$("aContainerSidebar").removeClass("radius");

		$("#toggleVideo").html('<img class="emoji" width="20" height="20" align="absmiddle" src="https://github.com/SCRAPTURE/jquery-emoji/raw/master/images/emojis/musical_note.png" alt="musical_note" title="hide video">');
		if(!toggle) $("#videoArea").css("bottom", "30px");
        localStorage.setItem("toggleVideo", true);
	}
	if (e) e.preventDefault();
}
function toggleChat(e) {
	if(toggle) {
		content.hide();
		sidebar.css("bottom", "0px");
		toggle = false;
		$("#toggleChat").html('<img class="emoji" width="20" height="20" align="absmiddle" src="https://github.com/SCRAPTURE/jquery-emoji/raw/master/images/emojis/arrow_up_small.png" alt="arrow_up_small" title="show chat">');
		if(showVideo) $("#videoArea").css("bottom", "30px");
        localStorage.setItem("toggleChat", true);
	}
	else {
		content.show();
		sidebar.css("bottom", "370px");
		toggle = true;
		content.css('border-left', '1px solid #333');
		content.css('border-right', '1px solid #333');
		$("#toggleChat").html('<img class="emoji" width="20" height="20" align="absmiddle" src="https://github.com/SCRAPTURE/jquery-emoji/raw/master/images/emojis/arrow_down_small.png" alt="arrow_down_small" title="hide chat">');
		if(showVideo) $("#videoArea").css("bottom", "400px");
        messages.animate({ scrollTop: messages.prop("scrollHeight")}, 500);
        localStorage.setItem("toggleChat", false);
	}
	if (e) e.preventDefault();
}
function toggleSound(e) {
		if (!mute) {
			mute = true;
			$('audio').each(function(){
				this.muted = true;
			});
			if (firstYoutube) {
                if (typeof(ytPlayer) == 'object') {
                    if (typeof(ytPlayer.mute) == 'function') ytPlayer.mute();
                }
            }          
			$("#toggleSound").html('<img class="emoji" width="20" height="20" align="absmiddle" src="https://github.com/SCRAPTURE/jquery-emoji/raw/master/images/emojis/mute.png" alt="mute" title="unmute">');
            localStorage.setItem("toggleSound", true);
		}
		else {
			mute = false;
			$('audio').each(function(){
				this.muted = false;
			});
			if (firstYoutube) ytPlayer.unMute();
			$("#toggleSound").html('<img class="emoji" width="20" height="20" align="absmiddle" src="https://github.com/SCRAPTURE/jquery-emoji/raw/master/images/emojis/speaker.png" alt="speaker" title="mute">');
            localStorage.setItem("toggleSound", false);
		}
		if (e) e.preventDefault();
}
function update(data) {
	data = $.parseJSON(data);
	if (data.status != 'success') {
		messages.append('<p class="msg red">Error sending/receiving data. Try refreshing. Perhaps you\'re not logged in ?</p>');
	}
	else {
		var howMany = 0
		
		if( messages[0].scrollHeight - messages.scrollTop() == messages.outerHeight()) var wasScrolled = true;
		else var wasScrolled = false;
		if (sidebar.css('display') == 'none' && input.is(':focus')) wasScrolled = true;
		$.each(data.messages, function(ID, message) {
			lastMsgID = ID;
			msg = message[1];
			if (msg == '/refresh' && message[0] == ';') {
				if (firstUpdateDone) {
					location.reload();
					return false;
				}
				else {
					return true;
				}
			}
			if (msg == '/skip' && message[0] == ';') {
				if (firstUpdateDone) {
					ytPlayer.stopVideo();
					onStateChange({data: 0});
					return true;
				}
				else {
					return true;
				}
			}
			if (msg == '/skip' && message[0] == nowPlayingNameA) {
                if (firstUpdateDone) {
                    ytPlayer.stopVideo();
                    onStateChange({data: 0});
                    return true;
                }
                else {
                    return true;
                }
			}
			var url = msg;
			var regExpT = /^.*(youtu.be\/|t\/|u\/\w\/|embed\/|watch\?t=|\&t=|\?t=)([^#\&\?]*).*/;
			var matchT = url.match(regExpT);
			if(matchT && matchT[2].length > 0) {
				var times = matchT[2].split(/[a-zA-Z]/);
				var startWhen = 0;
				if (times[2]) startWhen = parseInt(times[0]*60*60)+parseInt(times[1]*60)+parseInt(times[2]);
				else if (times[1]) startWhen = parseInt(times[0]*60)+parseInt(times[1]);
				else if (times[0]) startWhen = parseInt(times[0]);
				else startWhen = 0;
			}
			else var startWhen = 0;
			var regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=|\?v=)([^#\&\?]*).*/;
			var match = url.match(regExp);
			if (match && match[2].length == 11) {
				if (!ytPlaying) {
					var currentStamp = Math.floor(Date.now()/1000);
					startWhen += currentStamp - parseInt(message[2]) + timeDifference;
					if (startWhen < 0) startWhen = 0;
                                var YTurl = 'http://www.youtube.com/embed/' + match[2] + '?autoplay=0&enablejsapi=1&showinfo=0&controls=0&rel=0&?modestbranding=1&wmode=opaque&autohide=2'; //&origin=http://37.187.176.227/kag';
						if (typeof(ytPlayer) !== 'object') {
								setTimeout(function() {
									if (startWhen) YTurl = YTurl+ '&start=' + startWhen+3000;
									$('#videoObject').attr('src', YTurl );
									nowPlayingNameA = message[0];
									console.log("puszczam filmik: " + YTurl);
									//ytPlayer.loadVideoById(match[2], startWhen+3000, "default");
									//ytPlayer.playVideo();
								}, 3000);
						}
						else {
                                var YTurl = 'http://www.youtube.com/embed/' + match[2] + '?autoplay=0&enablejsapi=1&showinfo=0&controls=0&rel=0&?modestbranding=1&wmode=opaque&autohide=2'; //&origin=http://37.187.176.227/kag';
                            if (typeof(ytPlayer.loadVideoById) != 'function') {
                                if (startWhen) YTurl = YTurl+ '&start=' + startWhen;
                                $('#videoObject').attr('src', YTurl );
                                nowPlayingNameA = message[0];
								console.log("puszczam filmik2");
								//ytPlayer.playVideo();
                            }
                            else {
                                ytPlayer.loadVideoById({'videoId': match[2], startSeconds: startWhen});
                                //ytPlayer.playVideo();
                                nowPlayingNameA = message[0];
								console.log("puszczam filmik3");
								//ytPlayer.playVideo();
                                // console.log(typeof(ytPlayer));
                            }
						}
						
						$('audio').each(function(){
							this.pause();
							this.currentTime = 0;
						});	
						firstYoutube = true;
						if (mute) {
                            if (firstYoutube) {
                                if (typeof(ytPlayer) == 'object') {
                                    if (typeof(ytPlayer.mute) == 'function') ytPlayer.mute();
                                }
                            }  
						}
					messages.append('<p class="msg play">'+message[0]+' <span data-stamp='+(parseInt(message[2])*1000)+' title="'+$.timeago(new Date(parseInt(message[2]))*1000)+'">played <a target=_blank href="http://youtube.com/watch?v='+match[2]+'">a YT video</a>.</span></p>');
				}
			}
			else if (msg.substring(0,6) == '/play ' && !ytPlaying) {
				var sound = msg.split(' ')[1];
				if ($.inArray(sound, [ 'cottoneyejoe', 'trombone', 'nyan', 'yodel', 'deeper', 'trololo', 'crickets', 'tada', 'rimshot', 'yeah', 'noooo', 'pushin', 'cena', 'sax', 'titanic', 'pussy', 'dundundun', 'rick', 'trap', 'schnappi', 'duda' ]) > -1) {
					if (firstUpdateDone) { 
						$('audio').each(function(){
							this.pause();
							this.currentTime = 0;
						});
						nowPlayingNameA = message[0];
						messages.append('<p class="msg play">'+message[0]+' <span data-stamp='+(parseInt(message[2])*1000)+' title="'+$.timeago(new Date(parseInt(message[2]))*1000)+'">played '+sound+'.</span></p>');
						$.playSound('http://37.187.176.227/kag/music/'+sound);
						$('#videoTitle').html('').marquee('destroy');
						secs = false;
						if (mute) {
							$('audio').each(function(){
								this.muted = true;
							});	
						}
					}
				}
			}
            // else if (msg.substring(0,6) == '/me ') messages.append('<p class="msg me" title="'+$.timeago(new Date(parseInt(message[2]))*1000)+'">'+message[0]+' '+msg.substring(msg.length -)+'</p>');
			// zrobic else jesli to ja pisalem i upomniec ze nie moge
			else if(msg.substring(0,6) !== '/play ' && msg.substring(0,6) !== '/skip') messages.append('<p class=msg>'+message[0]+': <span data-stamp='+(parseInt(message[2])*1000)+' title="'+$.timeago(new Date(parseInt(message[2]))*1000)+'">'+msg+'</span></p>');
			if (firstUpdateDone && msg.toLowerCase().indexOf("było") >= 0 && msg.substring(0,6) != '/play ') $.playSound('http://37.187.176.227/kag/music/bylo2');
			else if (firstUpdateDone && msg.toLowerCase().indexOf("bylo") >= 0 && msg.substring(0,6) != '/play ') $.playSound('http://37.187.176.227/kag/music/bylo2');
			<?php if($player) { ?>
			if (firstUpdateDone && msg.toLowerCase().indexOf("<?php echo strtolower($player->name); ?>") >= 0 && vis() && doNotif) {
				$.playSound('http://37.187.176.227/kag/music/mention2');
			}
			if (firstUpdateDone && msg.toLowerCase().indexOf("<?php echo strtolower($player->name); ?>") >= 0 && !vis() && doNotif) {
				notify($("a.pName").last().attr("title") + ' mentioned you in the chat!');
			}<?php } ?>
			howMany++;
		});
		if (howMany > 0) {
			if(wasScrolled) { messages.animate({ scrollTop: messages.prop("scrollHeight")}, 500); }
			$('.msg').each(function(i, d){
				$(d).emoji().flag().linkify({target:"_blank"});
			});
		}
		if (visiting && data.visiting) {

			if (data.visiting != oldVisiting) {
		
				oldVisiting = data.visiting;
				visiting.html(data.visiting);
				visiting.emoji().flag();
			}
			
		}
	}
	firstUpdateDone = true;
}
$(document).keypress(function(e) {
    if(e.which == 13 && input.is(":focus") && $.trim(input.val()) != '' && input.val().match(/^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=|\?v=)([^#\&\?]*).*/) && !ytPlaying) {
	;
		var oldLast = lastMsgID;
			lastMsgID++;
			$.ajax({
					type: "POST",
					data: { msg: input.val(), lastMsgID: oldLast },
					url: "http://37.187.176.227/kag/box.php",
					success: function(data){
						update(data);
					
					}
			});
		input.val("");
	}
	else if(e.which == 13 && input.is(":focus") && $.trim(input.val()) != '' && !input.val().match(/^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=|\?v=)([^#\&\?]*).*/)) {
	;
		var oldLast = lastMsgID;
			lastMsgID++;
			$.ajax({
					type: "POST",
					data: { msg: input.val(), lastMsgID: oldLast },
					url: "http://37.187.176.227/kag/box.php",
					success: function(data){
						update(data);
					
					}
			});
		input.val("");
	}
});
window.setInterval(function(){
	$.ajax({
			type: "POST",
			data: { lastMsgID: lastMsgID },
			url: "http://37.187.176.227/kag/box.php",
			success: function(data){
				update(data);
			}
	});
    $('.msg span').each(function() { $(this).prop('title', $.timeago(new Date($(this).data('stamp')))) });
}, 5000);
window.setInterval(function(){
	if(secs) {
		secs--;
		$('#videoTime').html(timer(secs));
	}
	else {
		$('#videoTime').html('<br>');
	}
}, 1000);
	$.ajax({
			type: "POST",
			url: "http://37.187.176.227/kag/box.php",
			success: function(data){
				update(data);
			}
	});
$(".pName").flag().emoji();
setTimeout(function(){ doDing = true; }, 10000);
function onStateChange(event) {
	if(event.data === 1) {
		console.log("zaladowane");
		$('#videoTitle').marquee('destroy').html(ytPlayer.getVideoData().title).marquee({duration:7000,gap:30,direction:'left',duplicated:true});
		secs = (Math.round(ytPlayer.getDuration()-ytPlayer.getCurrentTime()));
		ytPlaying = true;
        if (ytPlayer.getDuration() > 20*60) {
			console.log("za dlugie");
            ytPlayer.stopVideo();
            onStateChange({data: 0});
            $("#messages").append('<p class="msg play"><span>Video skipped because it is too long (limit is 20 minutes).</span></p>');
            messages.animate({ scrollTop: messages.prop("scrollHeight")}, 500);
        }
	}
	if(event.data === 2) {
		console.log("zatrzymuje");
		ytPlayer.playVideo();
	}
	if(event.data === 0) {
		console.log("koniec");
		$('#videoTitle').html('').marquee('destroy');
		secs = false;
		ytPlaying = false;
	}
}
if ("Notification" in window) {
	if (Notification.permission !== "granted") {
		Notification.requestPermission();
	}	
}
function notify(message) {
	if ("Notification" in window) {
		if (Notification.permission === "granted") {
			var notification = new Notification(message);
		}	
	}
}
var vis = (function(){
    var stateKey, eventKey, keys = {
        hidden: "visibilitychange",
        webkitHidden: "webkitvisibilitychange",
        mozHidden: "mozvisibilitychange",
        msHidden: "msvisibilitychange"
    };
    for (stateKey in keys) {
        if (stateKey in document) {
            eventKey = keys[stateKey];
            break;
        }
    }
    return function(c) {
        if (c) document.addEventListener(eventKey, c);
        return !document[stateKey];
    }
})();
function fetchAsBody(url, presentedUrl) {
	$.ajax({
			type: "GET",
			url: url,
			success: function(data){
				$('#bodyContent').html(data);
				$('.pName').flag().emoji();
				jQuery("time.timeago").timeago();
				history.pushState({ presentedUrl: presentedUrl, url: url }, $('#dataDiv').data('title'), presentedUrl);
				document.title = $('#dataDiv').data('title');
			}
	});
}
$(window).bind('popstate', function(event) {
	window.location.href = location.pathname;
});
function mainPage(e) {
	e.preventDefault();
	fetchAsBody('http://37.187.176.227/kag/index.php?body=only', 'http://37.187.176.227/kag/');

}
function mePage(e) {
	e.preventDefault();
	fetchAsBody('http://37.187.176.227/kag/me.php?body=only', 'http://37.187.176.227/kag/me');

}
function pPage(e) {
	e.preventDefault();

	fetchAsBody('http://37.187.176.227/kag/p.php?body=only&name='+e.currentTarget.title, 'http://37.187.176.227/kag/p/'+e.currentTarget.title);
	
}
function sPage(e) {
	e.preventDefault();
	fetchAsBody('http://37.187.176.227/kag/s.php?body=only&id='+$(e.target).data('serverid'), 'http://37.187.176.227/kag/s/'+$(e.target).data('serverid'));
	
}
function testFetch() {
	fetchAsBody('http://37.187.176.227/kag/p.php?body=only&name=Konfitur', 'http://37.187.176.227/kag/p/Konfitur');
}
jQuery(document).ready(function() {jQuery("time.timeago").timeago();});
window.setInterval(function(){
	if (window.location.href == 'http://37.187.176.227/kag/') {fetchAsBody('http://37.187.176.227/kag/index.php?body=only', 'http://37.187.176.227/kag/'); }
}, 60000);
if (localStorage.getItem("toggleNotif") == 'true') toggleNotif();
if (localStorage.getItem("toggleVideo") == 'true') toggleVideo();
if (localStorage.getItem("toggleChat") == 'true') toggleChat();
if (localStorage.getItem("toggleSound") == 'true') toggleSound();
</script>
<?php
		}
	}
	
