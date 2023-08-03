import websockets
import asyncio
import openai
import json

VALID_API_KEYS = ['AKIAIOSFODNN7EXAMPLE', 'valid-api-key-2', 'valid-api-key-3']
openai.api_key = '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'

async def handle_message(websocket, path):
    async for message in websocket:
        print(f"Received message: {message}")
            
        # Parse the message into a Python dictionary
        message_dict = json.loads(message)
        print(message_dict.get('apiKey', ''))

        api_key = message_dict.get('apiKey')
        if api_key not in VALID_API_KEYS:
            print(f"Invalid API key: {api_key}")
            return
        try: 
            response = openai.ChatCompletion.create(
                model='gpt-3.5-turbo',
                messages=[
                    {'role': 'system', 'content': message_dict.get('system', '')},
                    {'role': 'user', 'content': message_dict.get('user', '')},
                ],
                temperature=0.5,
                stream=True
            )

            for chunk in response:
                if 'delta' in chunk['choices'][0] and 'content' in chunk['choices'][0]['delta']:
                    chunk_message = chunk['choices'][0]['delta']['content']
                    print(chunk_message)
                    await websocket.send(chunk_message)
                elif 'finish_reason' in chunk['choices'][0]:
                    chunk_message = chunk['choices'][0]['finish_reason']
                    await websocket.send(chunk_message)
        except Exception as e: 
            print(f'Could not open socket: {e}')
            return

start_server = websockets.serve(handle_message, 'localhost', 5050)

asyncio.get_event_loop().run_until_complete(start_server)
asyncio.get_event_loop().run_forever()