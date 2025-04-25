<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

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
            $similarProductIds = $this->productSimilarity[$productId] ?? [];
            
            // Jika tidak ada produk serupa
            if (empty($similarProductIds)) {
                return response()->json([]);
            }
            
            // Ambil detail produk
            $recommendedProducts = Product::whereIn('id', $similarProductIds)->get();
            
            return response()->json($recommendedProducts);
        } catch (\Exception $e) {
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
            
            // Dalam implementasi nyata, dapatkan riwayat pesanan dari OrderService
            $orderServiceUrl = Config::get('services.order_service.url', 'http://order-service.test');
            $orderResponse = Http::get($orderServiceUrl . '/api/orders/user/' . $userId);
            
            if (!$orderResponse->successful()) {
                return response()->json([
                    'error' => 'Failed to fetch order history',
                    'details' => $orderResponse->json()
                ], $orderResponse->status());
            }
            
            // Ekstrak ID produk dari pesanan
            $purchaseHistory = collect($orderResponse->json())->pluck('product_id')->toArray();
            
            // Jika tidak ada riwayat pembelian
            if (empty($purchaseHistory)) {
                return response()->json([]);
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
            
            // Ambil detail produk
            $recommendedProducts = Product::whereIn('id', $recommendedProductIds)->get();
            
            return response()->json($recommendedProducts);
        } catch (\Exception $e) {
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
            
            // Dalam implementasi nyata, simpan ke database
            // Di sini kita update array statis untuk contoh
            if (!isset($this->userPurchaseHistory[$userId])) {
                $this->userPurchaseHistory[$userId] = [];
            }
            
            if (!in_array($productId, $this->userPurchaseHistory[$userId])) {
                $this->userPurchaseHistory[$userId][] = $productId;
            }
            
            return response()->json([
                'message' => 'Purchase history updated successfully',
                'history' => $this->userPurchaseHistory[$userId]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update purchase history',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}