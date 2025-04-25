<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class UserConsumerController extends Controller
{
    /**
     * Mendapatkan info penjual produk
     * CONSUMER: Endpoint ini mengambil data dari UserService
     */
    public function getProductSeller($productId)
    {
        try {
            // Dalam konteks nyata, kita perlu mendapatkan ID penjual dari database
            // Untuk contoh ini, kita asumsikan produk dengan ID tertentu dimiliki oleh user dengan ID 1
            $sellerId = 1;
            
            // Hardcode URL untuk pengujian
            $userServiceUrl = 'http://localhost:8001';
            
            // Log URL yang akan diakses
            Log::info("Trying to connect to: " . $userServiceUrl . '/api/users/' . $sellerId);
            
            // Panggil API UserService
            $response = Http::get($userServiceUrl . '/api/users/' . $sellerId);
            
            // Log response
            Log::info("Response status: " . $response->status());
            
            // Jika response berhasil
            if ($response->successful()) {
                return response()->json($response->json());
            }
            
            // Jika terjadi error dari UserService
            return response()->json([
                'error' => 'Failed to fetch seller info from UserService',
                'details' => $response->json()
            ], $response->status());
            
        } catch (\Exception $e) {
            // Log error detail
            Log::error("Connection error details: " . $e->getMessage());
            
            // Jika terjadi error koneksi atau lainnya
            return response()->json([
                'error' => 'Error connecting to UserService',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Mendapatkan daftar semua user
     * CONSUMER: Endpoint ini mengambil data dari UserService
     */
    public function getAllUsers()
    {
        try {
            // Hardcode URL untuk pengujian
            $userServiceUrl = 'http://localhost:8001';
            
            // Log URL yang akan diakses
            Log::info("Trying to connect to: " . $userServiceUrl . '/api/users');
            
            // Panggil API UserService
            $response = Http::get($userServiceUrl . '/api/users');
            
            // Log response
            Log::info("Response status: " . $response->status());
            
            // Jika response berhasil
            if ($response->successful()) {
                return response()->json($response->json());
            }
            
            // Jika terjadi error dari UserService
            return response()->json([
                'error' => 'Failed to fetch users from UserService',
                'details' => $response->json()
            ], $response->status());
            
        } catch (\Exception $e) {
            // Log error detail
            Log::error("Connection error details: " . $e->getMessage());
            
            // Jika terjadi error koneksi atau lainnya
            return response()->json([
                'error' => 'Error connecting to UserService',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}