<?php
namespace App\Http\Controllers;
use App\Models\Order;
use App\Models\Session;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Shopify\Clients\Graphql;
class Controller extends BaseController
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;
    public function updateOrderLineItemCustomAttributes(Request $request)
    {
        // Validate request
        // Log::info('Request: ' . json_encode($request->all()));
        try {
            // $validator = Validator::make($request->all(), [
            //     'orderId' => 'required',
            //     'shopDomain' => 'required',
            //     'lineItems' => 'required',
            // ]);
            // if ($validator->fails()) {
            //     return response()->json(['error' => $validator->errors()], 400);
            // }
            $recordCreated = $this->createOverrideOrderDataInDB($request->all());
            // Log::info("recordCreated", $recordCreated);
            // Log::info('Order line items updated successfully');
            return $recordCreated;
            // return response()->json(['message' => 'Order line items updated successfully'], 200);
        } catch (\Exception $e) {
            Log::info('Error: ' . $e->getMessage());
            return false;
            // return response()->json(['error' => $e->getMessage()], 400);
        }
    }
    public function createOverrideOrderDataInDB($orderInformation)
    {
       try {
         //find order by id if record found delete that and create an new record
        //  Log::info($orderInformation);
         $order = Order::where('orderId', $orderInformation['orderId'])->first();
         if ($order) {
            //  Log::info("Order found: " . json_encode($order));
             $order->delete();
         }
         $order = new Order();
         $order->orderId = $orderInformation['orderId'];
         $order->shopDomain = $orderInformation['shopDomain'];
         $order->lineItems = json_encode($orderInformation['lineItems']);
         $order->orderName = $orderInformation['orderName'];
         $order->customerName = $orderInformation['customerName'];
         $order->save();
         return $order;
               } catch (\Throwable $th) {
                Log::info($th);
       }
    }
    public function fetchOrderById(Request $request)
    {
        //sanitized orderId
        // $orderId = htmlspecialchars($orderId, ENT_QUOTES, 'UTF-8');
        $orderId = $request->input('orderId');
        // Log::info("orderId: " . $orderId);
        $order = Order::where('orderId', $orderId)->first();
        // Log::info("Order: " . json_encode($order));
        if ($order) {
            return $order;
            // return response()->json(['data' => json_decode($order->lineItems)], 200);
        } else {
            return null;
            // return response()->json(['error' => 'Order not found'], 404);
        }
    }
    public function fetchAllOrders(Request $request)
    {
        $shopDomain = $request->input('shopDomain');
        Log::info("Shop Domain: " . $shopDomain);
        $orders = Order::where('shopDomain', $shopDomain)->get();
        Log::info("Orders: " . json_encode($orders));
        if ($orders) {
            return $orders;
            // return response()->json(['data' => $orders], 200);
        } else {
            return null;
            // return response()->json(['error' => 'Orders not found'], 404);
        }
    }
    function fetchOrderByGid($gid, $shopDomain)
    {
        if ($shopDomain == null) {
            // return response()->json(['error' => 'Domain is required'], 400);
        }
        $shop = Session::where('shop', $shopDomain)->first();
        Log::info("Shop: " . json_encode($shop));
        $accessToken = $shop->access_token;
        Log::info("Access Token: " . $accessToken);
        $client = new Graphql($shopDomain, $accessToken);
        Log::info("Client: " . json_encode($client));
        $query = <<<QUERY
            query {
                node(id: $gid) {
                id
                ... on Order {
                    name
                    lineItems(first: 50) {
                    edges {
                        node {
                            fulfillmentStatus
                            id
                            isGiftCard
                            merchantEditable
                            name
                            nonFulfillableQuantity
                            originalTotal
                            originalUnitPrice
                            quantity
                            refundableQuantity
                            sku
                            taxable
                            title
                            totalDiscount
                            unfulfilledDiscountedTotal
                            unfulfilledOriginalTotal
                            unfulfilledQuantity
                            variantTitle
                            vendor
                            customAttributes {
                                key
                                value
                                }
                            }
                        }
                    }
                }
                }
            }
      QUERY;
        $response = $client->query(["query" => $query]);
        Log::info("Response: " . json_encode($response->getBody()));
        return json_decode($response->getBody(), true);
    }
}
