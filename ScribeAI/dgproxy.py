from deepgram import Deepgram
import asyncio
import websockets
import json

# Your personal API key
DEEPGRAM_API_KEY = 'a4bc78dce7fe303e08f70537a59c391587ca5a4f'

# Fill in these parameters to adjust the output as you wish!
# See our docs for more info: https://developers.deepgram.com/documentation/ 
PARAMS = {"punctuate": True, 
          "numerals": True,
          "model": "phonecall", 
          "language": "en-US",
          "diarize": True,
          "tier": "nova",
            }

# Set this variable to `True` if you wish only to 
# see the transcribed words, like closed captions. 
# Set it to `False` if you wish to see the raw JSON responses.
TRANSCRIPT_ONLY = True

'''
Function object.

Input: JSON data sent by a live transcription instance, which is named 
`deepgramLive` in main().

Output: The printed transcript obtained from the JSON object
'''
async def print_transcript(websocket, json_data):
    try:
      transcript = json_data['channel']['alternatives'][0]['transcript']
      print(transcript)
      # Send transcript back to the client
      await websocket.send(transcript)
    except KeyError:
      print()

async def main(websocket, path):
    # Initializes the Deepgram SDK
    deepgram = Deepgram(DEEPGRAM_API_KEY)
    # Create a websocket connection to Deepgram
    try:
        deepgramLive = await deepgram.transcription.live(PARAMS)
    except Exception as e:
        print(f'Could not open socket: {e}')
        return

    # Listen for the connection to close
    deepgramLive.registerHandler(deepgramLive.event.CLOSE, 
                                 lambda _: print('✅ Transcription complete! Connection closed. ✅'))

    # Listen for any transcripts received from Deepgram & write them to the console
    if TRANSCRIPT_ONLY:
        deepgramLive.registerHandler(deepgramLive.event.TRANSCRIPT_RECEIVED, 
                                  lambda data: asyncio.create_task(print_transcript(websocket, data)))
    else:
        deepgramLive.registerHandler(deepgramLive.event.TRANSCRIPT_RECEIVED, print)

    # Listen for the connection to open and send streaming audio from the WebSocket to Deepgram
    async for data in websocket:
        if data:
            deepgramLive.send(data)

    # Indicate that we've finished sending data by sending the customary 
    # zero-byte message to the Deepgram streaming endpoint, and wait 
    # until we get back the final summary metadata object
    await deepgramLive.finish()

start_server = websockets.serve(main, 'localhost', 5000)

asyncio.get_event_loop().run_until_complete(start_server)
asyncio.get_event_loop().run_forever()


