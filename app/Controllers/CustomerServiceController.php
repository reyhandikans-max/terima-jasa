<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Auth;
use App\Models\Category;
use App\Models\Service;
use App\Models\TechnicianProfile;

/**
 * CustomerServiceController - Mengelola Pencarian, Filter & Detail Katalog Jasa
 */
class CustomerServiceController extends Controller
{
    /**
     * Tampilkan Daftar Jasa (Browse, Filter Kategori, & Search)
     */
    public function index(Request $request): void
    {
        $categories = Category::all();
        $selectedCatId = $request->input('category_id');
        $search = trim((string)$request->input('search', $request->input('q', '')));

        $allServices = Service::all();
        $filteredServices = [];

        foreach ($allServices as $srv) {
            // Filter by Category
            if (!empty($selectedCatId) && (int)$srv->category_id !== (int)$selectedCatId) {
                continue;
            }

            // Filter by Search Query
            if (!empty($search)) {
                $haystack = strtolower($srv->name . ' ' . $srv->description);
                if (!str_contains($haystack, strtolower($search))) {
                    continue;
                }
            }

            $filteredServices[] = $srv;
        }

        // Cari selected category object jika ada
        $selectedCategory = !empty($selectedCatId) ? Category::find((int)$selectedCatId) : null;

        $this->view('customer/services/index', [
            'pageTitle'        => 'Katalog Layanan Jasa — Terima Jasa',
            'currentUser'      => Auth::user(),
            'categories'       => $categories,
            'services'         => $filteredServices,
            'selectedCatId'    => $selectedCatId,
            'selectedCategory' => $selectedCategory,
            'search'           => $search
        ], 'dashboard');
    }

    /**
     * Tampilkan Detail Layanan Jasa
     */
    public function show(Request $request, string $id): void
    {
        $service = Service::find((int)$id);

        if (!$service) {
            $_SESSION['flash_error'] = 'Layanan jasa yang Anda cari tidak ditemukan.';
            $this->redirect('customer/services');
            return;
        }

        $category = $service->category();
        $availableTechnicians = $service->availableTechnicians();

        $this->view('customer/services/show', [
            'pageTitle'            => e($service->name) . ' — Detail Layanan Terima Jasa',
            'currentUser'          => Auth::user(),
            'service'              => $service,
            'category'             => $category,
            'availableTechnicians' => $availableTechnicians
        ], 'dashboard');
    }
}
