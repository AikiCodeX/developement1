<?php

/**
 * Dictation form
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    cfapress <cfapress>
 * @author    Brady Miller <brady.g.miller@gmail.com>
 * @author    Robert Down <robertdown@live.com>
 * @copyright Copyright (c) 2008 cfapress <cfapress>
 * @copyright Copyright (c) 2013-2017 bradymiller <bradymiller@users.sourceforge.net>
 * @copyright Copyright (c) 2017-2023 Robert Down <robertdown@live.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 **/

require_once(__DIR__ . "/../../globals.php");
require_once("$srcdir/api.inc.php");

use OpenEMR\Common\Csrf\CsrfUtils;
use OpenEMR\Core\Header;

$returnurl = 'encounter_top.php';
?>
<html>
<head>
    <title><?php echo xlt("Dictation"); ?></title>
    <style>
  .textbox-container {
    position: relative;
  }

  #dictation-textarea {
    padding-right: 30px; /* Provide some space for the button on the right */
  }

  #mic-btn {
    position: absolute;
    top: 5px;
    right: 5px;
    font-size: 18px;
    background-color: transparent;
    border: none;
    cursor: pointer;
  }
</style>

    <?php Header::setupHeader();?>
</head>
<body>
    <div class="container mt-3">
        <div class="row">
            <div class="col-12">
                <h2><?php echo xlt("Dictation"); ?></h2>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <form name="my_form" method=post action="<?php echo $rootdir;?>/forms/dictation/save.php?mode=new" onsubmit="return top.restoreSession()">
                    <input type="hidden" name="csrf_token_form" value="<?php echo attr(CsrfUtils::collectCsrfToken()); ?>" />
                        <fieldset>
                            <legend><?php echo xlt('Dictation')?></legend>
                            <div class="container">  
                            <div class="form-group">
                                <div style="position: relative;">
                                    <textarea name="dictation" id="dictation-textarea" class="form-control" cols="80" rows="15"></textarea>
                                    <button id="mic-btn" onclick="event.preventDefault(); toggleRecording()">
                                        🎤
                                    </button>
                                </div>
                            </div>
                        </div>
                        </fieldset>
                        <fieldset>
                            <legend><?php echo xlt('Additional Notes'); ?></legend>
                            <div class="container">
                                <div class="form-group">
                                    <textarea name="additional_notes" class="form-control" cols="80" rows="5"></textarea>
                                </div>
                            </div>
                        </fieldset>
                    <div class="form-group">
                        <div class="btn-group" role="group">
                            <button type='submit' onclick='top.restoreSession()' class="btn btn-primary btn-save"><?php echo xlt('Save'); ?></button>
                            <button type="button" class="btn btn-secondary btn-cancel" onclick="top.restoreSession(); parent.closeTab(window.name, false);"><?php echo xlt('Cancel');?></button>
                        </div>
                    </div>
                    <script>
                        let isRecording = false;
                        let mediaRecorder;
                        let chunks = [];
                        let websocket;

                        function toggleRecording() {
                                if (isRecording) {
                                    stopRecording();
                                } else {
                                    startRecording();
                                }
                            }

                        function startRecording() {
                            chunks = [];
                            isRecording = true;

                            // Use the globally scoped websocket variable
                            websocket = new WebSocket('ws://localhost:5000');

                            websocket.onopen = function(event) {
                                // WebSocket is connected
                                console.log("WebSocket is open now.");
                                
                                // Get your text box element
                                var textBox = document.getElementById('dictation-textarea'); 
                            };

                            websocket.onmessage = function (event) {
                                // Handle server responses here
                                document.getElementById('dictation-textarea').value += event.data.replace('\n', ' ') + ' ';
                            };
                            

                            navigator.mediaDevices.getUserMedia({ audio: true, video: false })
                                .then(function(stream) {
                                    let options = {mimeType: 'audio/webm;codecs=opus'};
                                    mediaRecorder = new MediaRecorder(stream, options)
                                    mediaRecorder.ondataavailable = handleDataAvailable;
                                    mediaRecorder.onstop = function() {
                                        console.log("MediaRecorder stopped, chunks length: ", chunks.length);
                                    }
                                    console.log('MediaRecorder started, state: ', mediaRecorder.state);
                                    mediaRecorder.start(1000); // fires 'dataavailable' event every 1 second
                                })
                                .catch(function(err) {
                                    console.error('getUserMedia() failed: ', err);
                                });
                        }

                        function handleDataAvailable(event) {
                            if (event.data.size > 0) {
                                chunks.push(event.data);
                                if (websocket && websocket.readyState === WebSocket.OPEN) {
                                    websocket.send(event.data);
                                }
                                console.log('Data received, chunks length: ', chunks.length);
                            } else {
                                console.log('Data event triggered but no data.');
                            }
                        }

                        function stopRecording() {
                            isRecording = false;
                            if (mediaRecorder) {
                                console.log('Stop recording called, MediaRecorder state: ', mediaRecorder.state);
                                mediaRecorder.stop();
                            }
                            // check if the websocket connection is open before closing it
                            if (websocket && websocket.readyState === WebSocket.OPEN) {
                                websocket.close();
                            }
                        }


                    </script>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
