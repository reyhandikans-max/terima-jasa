<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Auth;
use App\Models\Category;
use App\Models\Service;

/**
 * AdminCategoryController — CRUD Manajemen Kategori Jasa
 */
class AdminCategoryController extends Controller
{
    /**
     * List Kategori (Read)
     */
    public function index(Request $request): void
    {
        $categories  = Category::all();
        $allServices = Service::all();

        $items = [];
        foreach ($categories as $cat) {
            $serviceCount = count(array_filter($allServices, fn($s) => (int)$s->category_id === (int)$cat->id));
            $items[] = [
                'category'     => $cat,
                'serviceCount' => $serviceCount,
            ];
        }

        $this->view('admin/categories/index', [
            'pageTitle'   => 'Kategori Layanan Jasa — Panel Admin',
            'currentUser' => Auth::user(),
            'items'       => $items,
        ], 'dashboard');
    }

    /**
     * Form Tambah Kategori (Create)
     */
    public function create(Request $request): void
    {
        $this->view('admin/categories/create', [
            'pageTitle'   => 'Tambah Kategori Baru — Panel Admin',
            'currentUser' => Auth::user(),
        ], 'dashboard');
    }

    /**
     * Simpan Kategori Baru (Store)
     */
    public function store(Request $request): void
    {
        $name        = trim((string)$request->input('name'));
        $description = trim((string)$request->input('description'));
        $status      = trim((string)$request->input('status', 'active'));

        if (empty($name) || strlen($name) < 3) {
            $_SESSION['flash_error'] = 'Nama kategori wajib diisi minimal 3 karakter.';
            $this->redirect('admin/categories/create');
            return;
        }

        $cat = new Category([
            'name'        => $name,
            'description' => $description,
            'status'      => in_array($status, ['active', 'inactive']) ? $status : 'active',
        ]);
        $cat->save();

        $_SESSION['flash_success'] = "Kategori '{$name}' berhasil ditambahkan ke dalam katalog.";
        $this->redirect('admin/categories');
    }

    /**
     * Form Edit Kategori (Edit)
     */
    public function edit(Request $request, string $id): void
    {
        $category = Category::find((int)$id);

        if (!$category) {
            $_SESSION['flash_error'] = 'Kategori tidak ditemukan.';
            $this->redirect('admin/categories');
            return;
        }

        $this->view('admin/categories/edit', [
            'pageTitle'   => 'Edit Kategori: ' . e($category->name) . ' — Panel Admin',
            'currentUser' => Auth::user(),
            'category'    => $category,
        ], 'dashboard');
    }

    /**
     * Update Kategori (Update)
     */
    public function update(Request $request, string $id): void
    {
        $category = Category::find((int)$id);

        if (!$category) {
            $_SESSION['flash_error'] = 'Kategori tidak ditemukan.';
            $this->redirect('admin/categories');
            return;
        }

        $name        = trim((string)$request->input('name'));
        $description = trim((string)$request->input('description'));
        $status      = trim((string)$request->input('status', 'active'));

        if (empty($name) || strlen($name) < 3) {
            $_SESSION['flash_error'] = 'Nama kategori wajib diisi minimal 3 karakter.';
            $this->redirect("admin/categories/{$id}/edit");
            return;
        }

        $category->name        = $name;
        $category->description = $description;
        $category->status      = in_array($status, ['active', 'inactive']) ? $status : 'active';
        $category->save();

        $_SESSION['flash_success'] = "Kategori '{$category->name}' berhasil diperbarui.";
        $this->redirect('admin/categories');
    }

    /**
     * Hapus Kategori (Delete)
     */
    public function delete(Request $request, string $id): void
    {
        $category = Category::find((int)$id);

        if (!$category) {
            $_SESSION['flash_error'] = 'Kategori tidak ditemukan.';
            $this->redirect('admin/categories');
            return;
        }

        // Cek jika kategori masih memiliki layanan terkait
        $allServices = Service::all();
        $hasServices = array_filter($allServices, fn($s) => (int)$s->category_id === (int)$category->id);

        if (!empty($hasServices)) {
            $_SESSION['flash_error'] = "Kategori '{$category->name}' tidak dapat dihapus karena masih menampung " . count($hasServices) . " layanan jasa. Hapus atau pindahkan layanan tersebut terlebih dahulu.";
            $this->redirect('admin/categories');
            return;
        }

        $name = $category->name;
        $category->delete();

        $_SESSION['flash_success'] = "Kategori '{$name}' berhasil dihapus.";
        $this->redirect('admin/categories');
    }
}
