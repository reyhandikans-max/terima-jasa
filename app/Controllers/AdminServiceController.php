<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Auth;
use App\Models\Service;
use App\Models\Category;

/**
 * AdminServiceController — CRUD Manajemen Layanan Jasa
 */
class AdminServiceController extends Controller
{
    /**
     * List Layanan Jasa (Read)
     */
    public function index(Request $request): void
    {
        $allServices = Service::all();
        $categories  = Category::all();

        $selectedCatId = $request->input('category_id');
        $search        = trim((string)$request->input('search', $request->input('q', '')));

        $filteredServices = [];
        foreach ($allServices as $srv) {
            // Filter Kategori
            if (!empty($selectedCatId) && (int)$srv->category_id !== (int)$selectedCatId) {
                continue;
            }

            // Filter Pencarian
            if (!empty($search)) {
                $haystack = strtolower(($srv->name ?? '') . ' ' . ($srv->description ?? ''));
                if (!str_contains($haystack, strtolower($search))) {
                    continue;
                }
            }

            $filteredServices[] = $srv;
        }

        // Sort desc by id
        usort($filteredServices, fn($a, $b) => (int)$b->id <=> (int)$a->id);

        $this->view('admin/services/index', [
            'pageTitle'        => 'Katalog Layanan Jasa — Panel Admin',
            'currentUser'      => Auth::user(),
            'services'         => $filteredServices,
            'categories'       => $categories,
            'selectedCatId'    => $selectedCatId,
            'search'           => $search,
            'totalCount'       => count($allServices),
        ], 'dashboard');
    }

    /**
     * Form Tambah Jasa (Create)
     */
    public function create(Request $request): void
    {
        $categories = Category::all();

        $this->view('admin/services/create', [
            'pageTitle'   => 'Tambah Layanan Jasa Baru — Panel Admin',
            'currentUser' => Auth::user(),
            'categories'  => $categories,
        ], 'dashboard');
    }

    /**
     * Simpan Jasa Baru (Store)
     */
    public function store(Request $request): void
    {
        $categoryId = (int)$request->input('category_id');
        $name       = trim((string)$request->input('name'));
        $desc       = trim((string)$request->input('description'));
        $price      = (float)$request->input('estimated_price', 0);
        $status     = trim((string)$request->input('status', 'active'));

        if (empty($categoryId) || empty($name)) {
            $_SESSION['flash_error'] = 'Kategori dan nama layanan wajib diisi.';
            $this->redirect('admin/services/create');
            return;
        }

        $service = new Service([
            'category_id'     => $categoryId,
            'name'            => $name,
            'description'     => $desc,
            'estimated_price' => $price,
            'status'          => in_array($status, ['active', 'inactive']) ? $status : 'active',
        ]);
        $service->save();

        $_SESSION['flash_success'] = "Layanan jasa '{$name}' berhasil ditambahkan ke katalog.";
        $this->redirect('admin/services');
    }

    /**
     * Form Edit Jasa (Edit)
     */
    public function edit(Request $request, string $id): void
    {
        $service = Service::find((int)$id);

        if (!$service) {
            $_SESSION['flash_error'] = 'Layanan jasa tidak ditemukan.';
            $this->redirect('admin/services');
            return;
        }

        $categories = Category::all();

        $this->view('admin/services/edit', [
            'pageTitle'   => 'Edit Layanan: ' . e($service->name) . ' — Panel Admin',
            'currentUser' => Auth::user(),
            'service'     => $service,
            'categories'  => $categories,
        ], 'dashboard');
    }

    /**
     * Update Jasa (Update)
     */
    public function update(Request $request, string $id): void
    {
        $service = Service::find((int)$id);

        if (!$service) {
            $_SESSION['flash_error'] = 'Layanan jasa tidak ditemukan.';
            $this->redirect('admin/services');
            return;
        }

        $categoryId = (int)$request->input('category_id');
        $name       = trim((string)$request->input('name'));
        $desc       = trim((string)$request->input('description'));
        $price      = (float)$request->input('estimated_price', 0);
        $status     = trim((string)$request->input('status', 'active'));

        if (empty($categoryId) || empty($name)) {
            $_SESSION['flash_error'] = 'Kategori dan nama layanan wajib diisi.';
            $this->redirect("admin/services/{$id}/edit");
            return;
        }

        $service->category_id     = $categoryId;
        $service->name            = $name;
        $service->description     = $desc;
        $service->estimated_price = $price;
        $service->status          = in_array($status, ['active', 'inactive']) ? $status : 'active';
        $service->save();

        $_SESSION['flash_success'] = "Layanan jasa '{$service->name}' berhasil diperbarui.";
        $this->redirect('admin/services');
    }

    /**
     * Hapus Jasa (Delete)
     */
    public function delete(Request $request, string $id): void
    {
        $service = Service::find((int)$id);

        if (!$service) {
            $_SESSION['flash_error'] = 'Layanan jasa tidak ditemukan.';
            $this->redirect('admin/services');
            return;
        }

        $name = $service->name;
        $service->delete();

        $_SESSION['flash_success'] = "Layanan jasa '{$name}' berhasil dihapus dari sistem.";
        $this->redirect('admin/services');
    }
}
