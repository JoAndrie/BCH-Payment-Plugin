from flask import Flask, request
from flask import jsonify
from flask_cors import CORS
from woocommerce import API

app = Flask(__name__)
CORS(app)

@app.route('/process_order', methods=['POST'])
def process_order():
    data = request.get_json()
    consumer_key = data['consumer_key']
    consumer_secret = data['consumer_secret']
    order_id = data['order_id']
    
    # Do something with the data
    # ...
    wcapi = API(
        url="https://paytaca-test.local/",
        consumer_key=consumer_key,
        consumer_secret=consumer_secret,
        version="wc/v3",
        verify_ssl=False
    )

    endpoint = "orders/" + str(order_id)
    data = {'status': 'completed'}
    r = wcapi.put(endpoint, data)

    print(r.status_code)
    print(r.request.url)

    result = {'message': 'Success!'}
    
    return jsonify(result)


