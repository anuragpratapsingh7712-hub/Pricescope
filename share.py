import sys
import time
from pyngrok import ngrok

# MAMP default port
PORT = 8888
TOKEN = "36Ea7FbJDHLUKDfof9N95tae9uh_4P5Ecxd3bSrcKrmF1gNAG"

try:
    # Set auth token
    ngrok.set_auth_token(TOKEN)

    # Open a HTTP tunnel on the default port 8888
    public_url = ngrok.connect(PORT).public_url
    print(f"🚀 PriceScope is now live at: {public_url}")
    print("Press Ctrl+C to stop sharing.")
    
    # Keep the script running
    while True:
        time.sleep(1)

except KeyboardInterrupt:
    print("\n🛑 Sharing stopped.")
    sys.exit(0)
except Exception as e:
    print(f"Error: {e}")
    sys.exit(1)
