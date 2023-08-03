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
 * @copyright Copyright (c) 2013-2019 bradymiller <bradymiller@users.sourceforge.net>
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
<body class="body_top">
<?php
$obj = formFetch("form_dictation", $_GET["id"]);
?>
<div class="container">
    <div class="row">
        <div class="col-12">
            <h2><?php echo xlt("Dictation"); ?></h2>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <form method=post action="<?php echo $rootdir?>/forms/dictation/save.php?mode=update&id=<?php echo attr_url($_GET["id"]);?>" name="my_form">
                <input type="hidden" name="csrf_token_form" value="<?php echo attr(CsrfUtils::collectCsrfToken()); ?>" />
                <fieldset>
                    <legend class=""><?php echo xlt('Dictation')?></legend>
                    <div class="form-group">
                        <div class="col-sm-10 offset-sm-1">
                            <div style="position: relative;">
                            <textarea name="dictation" id="dictation-textarea" class="form-control" cols="80" rows="15" ><?php echo text($obj["dictation"]);?></textarea>
                                <button id="mic-btn" onclick="event.preventDefault(); toggleRecording()">
                                    🎤
                                </button>
                            </div>
                        </div>
                    </div>
                </fieldset>
                <fieldset>
                    <legend class=""><?php echo xlt('Additional Notes'); ?></legend>
                    <div class="form-group">
                        <div class="col-sm-10 offset-sm-1">
                            <textarea name="additional_notes" class="form-control" cols="80" rows="5" ><?php echo text($obj["additional_notes"]);?></textarea>
                        </div>
                    </div>
                </fieldset>
                <div class="form-group clearfix">
                    <div class="col-sm-12 offset-sm-1 position-override">
                        <div class="btn-group" role="group">
                            <button type='submit' onclick='top.restoreSession()' class="btn btn-secondary btn-save"><?php echo xlt('Save'); ?></button>
                            <button type="button" class="btn btn-link btn-cancel" onclick="top.restoreSession(); parent.closeTab(window.name, false);"><?php echo xlt('Cancel');?></button>
                        </div>
                    </div>
                </div>
                <script>
                            let isRecording = false;
                            let mediaRecorder;
                            let recordedChunks = [];

                            function toggleRecording() {
                                if (isRecording) {
                                    stopRecording();
                                } else {
                                    startRecording();
                                }
                            }
                            function startRecording() {
                                if (!isRecording) {
                                    navigator.mediaDevices.getUserMedia({ audio: true })
                                        .then(function (stream) {
                                            isRecording = true;
                                            recordedChunks = [];
                                            mediaRecorder = new MediaRecorder(stream);

                                            mediaRecorder.ondataavailable = function (event) {
                                                if (event.data.size > 0) {
                                                    recordedChunks.push(event.data);
                                                }
                                            };

                                            mediaRecorder.onstop = function () {
                                                isRecording = false;
                                                const blob = new Blob(recordedChunks, { type: 'audio/webm' });
                                                const audioURL = URL.createObjectURL(blob);
                                                const textarea = document.getElementById('dictation-textarea');
                                                textarea.value += `\n[Audio Recording]\n${audioURL}\n[/Audio Recording]\n`;
                                                recordedChunks = [];
                                            };

                                            mediaRecorder.start();
                                            document.getElementById('mic-btn').innerText = '🎤 Stop';
                                        })
                                        .catch(function (error) {
                                            console.error('Error accessing the microphone:', error);
                                        });
                                }
                            }

                            function stopRecording() {
                                if (mediaRecorder && isRecording) {
                                    mediaRecorder.stop();
                                    document.getElementById('mic-btn').innerText = '🎤';
                                }
                            }
                    </script>
            </form>
        </div>
    </div>
</div>
</body>
</html>
