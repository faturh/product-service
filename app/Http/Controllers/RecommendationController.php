<?php
namespace App\Http\Controllers;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RecommendationController extends Controller
{
    // Data model sederhana (dalam praktiknya akan menggunakan ML library)
    private $productSimilarity = [
        1 => [2, 3, 5], // Produk dengan ID 1 mirip dengan produk 2, 3, dan 5
        2 => [1, 4, 6],
        3 => [1, 5, 7],
        4 => [2, 6, 8],
        5 => [1, 3, 7]
    ];
    
    // Data riwayat pembelian user (dalam praktiknya akan disimpan di database)
    private $userPurchaseHistory = [
        1 => [1, 3, 5],
        2 => [2, 4, 6]
    ];
    
    /**
     * Mendapatkan rekomendasi produk berdasarkan produk yang sedang dilihat
     * PROVIDER: Endpoint ini menyediakan rekomendasi produk
     */
    public function getSimilarProducts($productId)
    {
        try {
            $productId = (int) $productId;
            
            // Log untuk debugging
            Log::info('Getting similar products for product ID: ' . $productId);
            
            // Cek dulu apakah produk dengan ID ini ada di database
            $product = Product::find($productId);
            if (!$product) {
                Log::warning('Product not found: ' . $productId);
                
                // Tambahkan produk dummy untuk testing jika tidak ada
                $this->createDummyProducts();
                
                // Kembalikan array kosong untuk produk yang tidak ditemukan
                return response()->json([]);
            }
            
            // Gunakan array similarity atau buat fallback jika tidak ada
            $similarProductIds = $this->productSimilarity[$productId] ?? [];
            
            // Jika tidak ada produk serupa, ambil produk acak untuk testing
            if (empty($similarProductIds)) {
                Log::info('No similar products defined, using random products');
                $recommendedProducts = Product::where('id', '!=', $productId)
                    ->inRandomOrder()
                    ->limit(3)
                    ->get();
                    
                return response()->json($recommendedProducts);
            }
            
            // Ambil detail produk
            $recommendedProducts = Product::whereIn('id', $similarProductIds)->get();
            
            // Jika tidak ada produk yang ditemukan, ambil beberapa produk acak
            if ($recommendedProducts->isEmpty()) {
                Log::info('Similar products not found in database, using random products');
                $recommendedProducts = Product::where('id', '!=', $productId)
                    ->inRandomOrder()
                    ->limit(3)
                    ->get();
            }
            
            Log::info('Found ' . $recommendedProducts->count() . ' similar products');
            return response()->json($recommendedProducts);
        } catch (\Exception $e) {
            Log::error('Error getting similar products: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to generate recommendations',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Mendapatkan rekomendasi produk berdasarkan riwayat pembelian user
     * PROVIDER: Endpoint ini menyediakan rekomendasi produk
     * CONSUMER: Endpoint ini mengambil data pesanan dari OrderService
     */
    public function getUserRecommendations($userId)
    {
        try {
            $userId = (int) $userId;
            Log::info('Getting recommendations for user ID: ' . $userId);
            
            // Gunakan hardcoded URL untuk order service
            $orderServiceUrl = 'http://localhost:8003';
            Log::info('Connecting to order service: ' . $orderServiceUrl . '/api/orders/user/' . $userId);
            
            try {
                // Dapatkan riwayat pesanan dari OrderService
                $orderResponse = Http::get($orderServiceUrl . '/api/orders/user/' . $userId);
                
                if ($orderResponse->successful()) {
                    // Ekstrak ID produk dari pesanan
                    $purchaseHistory = collect($orderResponse->json())->pluck('product_id')->toArray();
                    Log::info('Retrieved purchase history from order service', ['products' => $purchaseHistory]);
                } else {
                    Log::warning('Failed to get orders from OrderService', [
                        'status' => $orderResponse->status(),
                        'body' => $orderResponse->body()
                    ]);
                    // Fallback ke data statis
                    $purchaseHistory = $this->userPurchaseHistory[$userId] ?? [];
                    Log::info('Using fallback purchase history', ['products' => $purchaseHistory]);
                }
            } catch (\Exception $e) {
                Log::error('Error connecting to OrderService: ' . $e->getMessage());
                // Fallback ke data statis
                $purchaseHistory = $this->userPurchaseHistory[$userId] ?? [];
                Log::info('Using fallback purchase history due to error', ['products' => $purchaseHistory]);
            }
            
            // Jika tidak ada riwayat pembelian, berikan produk acak saja
            if (empty($purchaseHistory)) {
                Log::info('No purchase history for user, using random products');
                $recommendedProducts = Product::inRandomOrder()->limit(5)->get();
                
                // Jika tidak ada produk di database, buat produk dummy
                if ($recommendedProducts->isEmpty()) {
                    $this->createDummyProducts();
                    $recommendedProducts = Product::inRandomOrder()->limit(5)->get();
                }
                
                return response()->json($recommendedProducts);
            }
            
            // Algoritma sederhana: Menemukan produk serupa dari semua produk yang pernah dibeli
            $recommendedProductIds = [];
            foreach ($purchaseHistory as $productId) {
                $similarIds = $this->productSimilarity[$productId] ?? [];
                foreach ($similarIds as $id) {
                    // Tidak merekomendasikan produk yang sudah dibeli
                    if (!in_array($id, $purchaseHistory) && !in_array($id, $recommendedProductIds)) {
                        $recommendedProductIds[] = $id;
                    }
                }
            }
            
            // Jika tidak ada rekomendasi dari algoritma, ambil beberapa produk acak
            if (empty($recommendedProductIds)) {
                Log::info('No recommendations from algorithm, using random products');
                $recommendedProducts = Product::whereNotIn('id', $purchaseHistory)
                    ->inRandomOrder()
                    ->limit(5)
                    ->get();
                    
                // Jika masih tidak ada, ambil produk apa saja
                if ($recommendedProducts->isEmpty()) {
                    $recommendedProducts = Product::inRandomOrder()->limit(5)->get();
                    
                    // Jika database kosong, buat produk dummy
                    if ($recommendedProducts->isEmpty()) {
                        $this->createDummyProducts();
                        $recommendedProducts = Product::inRandomOrder()->limit(5)->get();
                    }
                }
                
                return response()->json($recommendedProducts);
            }
            
            // Ambil detail produk dari rekomendasi algoritma
            $recommendedProducts = Product::whereIn('id', $recommendedProductIds)->get();
            
            // Jika produk dari algoritma tidak ditemukan di database, ambil produk acak
            if ($recommendedProducts->isEmpty()) {
                Log::info('Recommended products not found in database, using random products');
                $recommendedProducts = Product::inRandomOrder()->limit(5)->get();
                
                // Jika database kosong, buat produk dummy
                if ($recommendedProducts->isEmpty()) {
                    $this->createDummyProducts();
                    $recommendedProducts = Product::inRandomOrder()->limit(5)->get();
                }
            }
            
            Log::info('Found ' . $recommendedProducts->count() . ' recommended products for user');
            return response()->json($recommendedProducts);
        } catch (\Exception $e) {
            Log::error('Error getting user recommendations: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to generate user recommendations',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Memperbarui data pembelian setelah order baru
     * CONSUMER: Endpoint ini menerima notifikasi dari OrderService
     */
    public function updatePurchaseHistory(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|integer',
                'product_id' => 'required|integer',
            ]);
            
            $userId = $request->user_id;
            $productId = $request->product_id;
            
            Log::info('Updating purchase history', [
                'user_id' => $userId,
                'product_id' => $productId
            ]);
            
            // Dalam implementasi nyata, simpan ke database
            // Di sini kita update array statis untuk contoh
            if (!isset($this->userPurchaseHistory[$userId])) {
                $this->userPurchaseHistory[$userId] = [];
            }
            
            if (!in_array($productId, $this->userPurchaseHistory[$userId])) {
                $this->userPurchaseHistory[$userId][] = $productId;
            }
            
            Log::info('Purchase history updated successfully', [
                'user_id' => $userId,
                'history' => $this->userPurchaseHistory[$userId]
            ]);
            
            return response()->json([
                'message' => 'Purchase history updated successfully',
                'history' => $this->userPurchaseHistory[$userId]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update purchase history: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to update purchase history',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Helper method untuk membuat produk dummy jika database kosong
     */
    private function createDummyProducts()
    {
        try {
            // Periksa apakah sudah ada produk
            if (Product::count() > 0) {
                return;
            }
            
            Log::info('Creating dummy products');
            
            // Buat beberapa produk dummy
            $products = [
                [
                    'name' => 'Smartphone X',
                    'description' => 'Latest smartphone with advanced features',
                    'price' => 5000000,
                    'stock' => 10
                ],
                [
                    'name' => 'Laptop Pro',
                    'description' => 'Powerful laptop for professionals',
                    'price' => 15000000,
                    'stock' => 5
                ],
                [
                    'name' => 'Wireless Headphones',
                    'description' => 'High quality sound with noise cancellation',
                    'price' => 1500000,
                    'stock' => 20
                ],
                [
                    'name' => 'Smart Watch',
                    'description' => 'Track your fitness and stay connected',
                    'price' => 2500000,
                    'stock' => 15
                ],
                [
                    'name' => 'Tablet Ultra',
                    'description' => 'Slim and powerful tablet for entertainment',
                    'price' => 4000000,
                    'stock' => 8
                ]
            ];
            
            foreach ($products as $productData) {
                Product::create($productData);
            }
            
            Log::info('Created 5 dummy products');
            
        } catch (\Exception $e) {
            Log::error('Error creating dummy products: ' . $e->getMessage());
        }
    }
}