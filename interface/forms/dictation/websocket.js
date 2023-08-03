const WebSocket = require('ws');
const fs = require('fs');
const {
    TranscribeStreamingClient,
    StartStreamTranscriptionCommand,
} = require("@aws-sdk/client-transcribe-streaming");

// Create a TranscribeStreamingClient
const credentials = {
        accessKeyId: 'AKIAWEQUKRYF4BYX2OVV',
        secretAccessKey: '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'
    };


const wss = new WebSocket.Server({ port: 8090 });
const LanguageCode = 'en-US';
const MediaEncoding = 'ogg-opus';
const MediaSampleRateHertz = '16000';

let counter = 0;


wss.on('connection', ws => {
  console.log('Client connected');
  
  ws.on('message', message => {
    console.log('Received message from client');

    if (message instanceof Buffer) {
      // Save the audio chunk as a file for inspection
      const filename = `audioChunk${counter++}.opus`;
      fs.writeFileSync(filename, message);

      console.log(`Saved audio chunk as ${filename}`);
    } else {
      console.error('Received message is not binary data');
    }
  });

  // Create a generator function for the audio stream
  async function* audioStream() {
    const messageQueue = [];
    ws.on('message', message => {
      messageQueue.push(message);
      console.log(`Received message of size: ${message.length}`);
    });
    while (ws.readyState !== ws.CLOSED) {
      if (messageQueue.length > 0) {
        const message = messageQueue.shift();
        if (message instanceof Buffer) {
          // The message is binary data. Yield it to the stream.
          yield { AudioEvent: { AudioChunk: message } };
        } else {
          console.error('Received message is not binary data');
        }
      } else {
        // If there are no messages, wait a bit before checking again
        await new Promise(resolve => setTimeout(resolve, 100));
      }
    }
  }
  
  // Start transcription request when connection is established
  startRequest(audioStream()).catch(err => console.error(err));
});

async function startRequest(audioStream) {
  const client = new TranscribeStreamingClient({
    region: "us-east-1",
    credentials
  });

  const params = {
    LanguageCode,
    MediaEncoding,
    MediaSampleRateHertz,
    AudioStream: audioStream,
  };

  const command = new StartStreamTranscriptionCommand(params);
  // Send transcription request
  const response = await client.send(command);
  // Start to print response
  try {
    for await (const event of response.TranscriptResultStream) {
      console.log(JSON.stringify(event));
    }
  } catch(err) {
    console.error(err);
  }
}

console.log('WebSocket server is running');