import asyncio
import websockets
from amazon_transcribe.client import TranscribeStreamingClient
from amazon_transcribe.handlers import TranscriptResultStreamHandler
from amazon_transcribe.model import TranscriptEvent

class MyEventHandler(TranscriptResultStreamHandler):
    def __init__(self, websocket, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.websocket = websocket

    async def handle_transcript_event(self, transcript_event: TranscriptEvent):
        results = transcript_event.transcript.results
        for result in results:
            for alt in result.alternatives:
                await self.websocket.send(alt.transcript)

async def websocket_audio_stream(websocket):
    async for message in websocket:
        yield message

async def write_chunks(stream, audio_stream):
    async for chunk in audio_stream:
        await stream.input_stream.send_audio_event(audio_chunk=chunk)
    await stream.input_stream.end_stream()

async def basic_transcribe(websocket):
    client = TranscribeStreamingClient(region="us-east-1")

    stream = await client.start_stream_transcription(
        language_code="en-US",
        media_sample_rate_hz=16000,
        media_encoding="ogg-opus"
    )

    handler = MyEventHandler(websocket, stream.output_stream)
    audio_stream = websocket_audio_stream(websocket)
    await asyncio.gather(write_chunks(stream, audio_stream), handler.handle_events())

async def handler(websocket, path):
    await basic_transcribe(websocket)

start_server = websockets.serve(handler, "localhost", 8765)

asyncio.get_event_loop().run_until_complete(start_server)
asyncio.get_event_loop().run_forever()
