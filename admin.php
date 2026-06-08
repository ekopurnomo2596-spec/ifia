<?php
// Pengaturan File Database dan Folder Upload
$dataFile = 'catalog.json';
$uploadDir = 'uploads/';

// 1. Buat folder 'uploads' otomatis jika belum ada
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// 2. Buat file 'catalog.json' otomatis jika belum ada
if (!file_exists($dataFile)) {
    file_put_contents($dataFile, json_encode([]));
}

// 3. LOGIKA MENYIMPAN DATA (Saat tombol Save ditekan)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = htmlspecialchars($_POST['name']);
    $category = htmlspecialchars($_POST['category']);
    $minOrder = htmlspecialchars($_POST['minOrder']);
    $imagePath = '';

    // Proses Upload Foto
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $fileName = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", basename($_FILES['image']['name']));
        $targetFilePath = $uploadDir . $fileName;
        
        // Pindahkan file dari memori sementara ke folder uploads/
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFilePath)) {
            $imagePath = $targetFilePath;
        }
    }

    // Ambil data JSON lama, tambahkan data baru, lalu simpan lagi
    $currentData = json_decode(file_get_contents($dataFile), true);
    $newProduct = [
        'id' => uniqid(),
        'name' => $name,
        'category' => $category,
        'minOrder' => $minOrder,
        'image' => $imagePath
    ];
    array_unshift($currentData, $newProduct); // Taruh di urutan paling atas
    file_put_contents($dataFile, json_encode($currentData, JSON_PRETTY_PRINT));

    // Refresh halaman agar tidak double-submit
    header("Location: admin.php");
    exit;
}

// 4. LOGIKA MENGHAPUS DATA (Saat ikon tong sampah ditekan)
if (isset($_GET['delete'])) {
    $idToDelete = $_GET['delete'];
    $currentData = json_decode(file_get_contents($dataFile), true);
    $newData = [];
    
    foreach ($currentData as $item) {
        if ($item['id'] !== $idToDelete) {
            $newData[] = $item;
        } else {
            // Hapus file foto fisiknya dari folder uploads/
            if (!empty($item['image']) && file_exists($item['image'])) {
                unlink($item['image']);
            }
        }
    }
    file_put_contents($dataFile, json_encode($newData, JSON_PRETTY_PRINT));
    header("Location: admin.php");
    exit;
}

// Ambil semua data produk untuk ditampilkan di tabel
$products = json_decode(file_get_contents($dataFile), true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | EMAHAIRSHOP</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Lato:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* (CSS INI SAMA PERSIS SEPERTI SEBELUMNYA AGAR TETAP ELEGAN) */
        :root { --black: #111111; --white: #ffffff; --gray: #666666; --light-gray: #f4f6f9; --green: #25D366; --dark-blue: #4a5568; --circle-pink: #d8546b; --border-color: #eaeaea; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Lato', sans-serif; background-color: var(--light-gray); color: var(--black); display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background-color: var(--black); color: var(--white); display: flex; flex-direction: column; position: fixed; height: 100%; }
        .sidebar-header { padding: 20px; text-align: center; border-bottom: 1px solid #333; }
        .sidebar-header h2 { font-family: 'Playfair Display', serif; font-size: 1.5rem; letter-spacing: 2px; }
        .sidebar-header span { font-size: 0.75rem; color: var(--green); font-weight: bold; letter-spacing: 1px; }
        .nav-menu { list-style: none; padding: 20px 0; flex: 1; }
        .nav-menu li { padding: 15px 25px; cursor: pointer; transition: 0.3s; display: flex; align-items: center; gap: 15px; font-weight: 700; font-size: 0.9rem; }
        .nav-menu li:hover, .nav-menu li.active { background-color: rgba(255, 255, 255, 0.1); color: var(--green); border-left: 4px solid var(--green); }
        .nav-menu i { width: 20px; text-align: center; font-size: 1.2rem; }
        .main-content { margin-left: 250px; flex: 1; padding: 30px; display: flex; flex-direction: column; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; background: var(--white); padding: 15px 25px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); }
        .header h1 { font-size: 1.5rem; color: var(--dark-blue); }
        .actions-bar { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .btn-add { background-color: var(--green); color: var(--white); border: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: 0.3s; }
        .btn-add:hover { background-color: #1ebe56; transform: translateY(-2px); }
        .table-container { background: var(--white); border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid var(--border-color); }
        th { background-color: #f8f9fa; font-size: 0.85rem; text-transform: uppercase; color: var(--gray); font-weight: 800; letter-spacing: 1px; }
        .prod-img { width: 60px; height: 60px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border-color); }
        .btn-action { background: none; border: none; cursor: pointer; font-size: 1.1rem; margin-right: 10px; transition: 0.3s; }
        .btn-delete { color: var(--circle-pink); } .btn-delete:hover { color: #b3394c; }
        
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); align-items: center; justify-content: center; }
        .modal.show { display: flex; }
        .modal-content { background: var(--white); width: 90%; max-width: 600px; border-radius: 12px; padding: 30px; position: relative; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 15px; margin-bottom: 20px; }
        .close-btn { font-size: 1.5rem; cursor: pointer; color: var(--gray); transition: 0.3s; }
        .close-btn:hover { color: var(--circle-pink); }
        .form-group { margin-bottom: 15px; display: flex; flex-direction: column; gap: 8px; }
        .form-group label { font-weight: 700; font-size: 0.85rem; color: var(--dark-blue); }
        .form-group input, .form-group select { padding: 12px; border: 1px solid #ccc; border-radius: 6px; font-family: 'Lato', sans-serif; font-size: 0.95rem; outline: none; }
        .upload-area { border: 2px dashed #ccc; border-radius: 8px; padding: 30px; text-align: center; cursor: pointer; transition: 0.3s; background: #fafafa; position: relative; }
        .upload-area:hover { border-color: var(--green); background: #f0fdf4; }
        .upload-area input[type="file"] { position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; }
        #imagePreview { max-width: 100%; max-height: 200px; margin-top: 15px; border-radius: 8px; display: none; object-fit: cover; }
        .modal-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; border-top: 1px solid var(--border-color); padding-top: 20px; }
        .btn-cancel { background: #f4f6f9; color: var(--gray); border: 1px solid #ccc; padding: 10px 20px; border-radius: 6px; font-weight: bold; cursor: pointer; }
        .btn-save { background: var(--black); color: var(--white); border: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; cursor: pointer; transition: 0.3s; }
        .btn-save:hover { background: var(--green); }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>EMAHAIRSHOP</h2>
            <span>ADMIN PANEL</span>
        </div>
        <ul class="nav-menu">
            <li class="active"><i class="fa-solid fa-box-open"></i> Catalog</li>
            <li><a href="index.html" style="color:inherit; text-decoration:none;"><i class="fa-solid fa-globe"></i> View Website</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="header">
            <h1>Catalog Management</h1>
        </div>

        <div class="actions-bar">
            <div></div> <!-- Spacing -->
            <button class="btn-add" onclick="openModal()"><i class="fa-solid fa-plus"></i> Add New Product</button>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Min. Order</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($products)): ?>
                        <tr><td colspan="5" style="text-align:center; padding:30px;">No products found. Start adding some!</td></tr>
                    <?php else: ?>
                        <?php foreach($products as $prod): ?>
                        <tr>
                            <td>
                                <?php if(!empty($prod['image']) && file_exists($prod['image'])): ?>
                                    <img src="<?= $prod['image'] ?>" class="prod-img">
                                <?php else: ?>
                                    <img src="https://via.placeholder.com/60" class="prod-img">
                                <?php endif; ?>
                            </td>
                            <td><strong><?= $prod['name'] ?></strong></td>
                            <td><?= $prod['category'] ?></td>
                            <td><?= $prod['minOrder'] ?></td>
                            <td>
                                <a href="?delete=<?= $prod['id'] ?>" class="btn-action btn-delete" onclick="return confirm('Are you sure you want to delete this product?');" title="Delete">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Modal Form -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Product</h3>
                <span class="close-btn" onclick="closeModal()"><i class="fa-solid fa-xmark"></i></span>
            </div>
            
            <!-- Perhatikan atribut enctype wajib ada untuk mengupload file via PHP -->
            <form action="admin.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label>Product Name</label>
                    <input type="text" name="name" placeholder="e.g. Premium Hair Bulk" required>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category" required>
                            <option value="">Select Category...</option>
                            <option value="Hair Bulk">Hair Bulk</option>
                            <option value="Hair Weft">Hair Weft</option>
                            <option value="Hair Tape">Hair Tape</option>
                            <option value="I Tip / Keratin">I Tip / Keratin</option>
                            <option value="Nano Ring">Nano Ring</option>
                            <option value="Genius Weft">Genius Weft</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Minimum Order</label>
                        <input type="text" name="minOrder" placeholder="e.g. 500 Gram" value="500 Gram" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Upload Product Photo</label>
                    <div class="upload-area">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        <p>Click to browse image</p>
                        <input type="file" name="image" accept="image/*" onchange="previewImage(event)" required>
                        <img id="imagePreview" alt="Image Preview">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-save"><i class="fa-solid fa-floppy-disk"></i> Save Product</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() { document.getElementById('productModal').classList.add('show'); }
        function closeModal() { document.getElementById('productModal').classList.remove('show'); }
        function previewImage(event) {
            const reader = new FileReader();
            const imageField = document.getElementById("imagePreview");
            reader.onload = function() {
                if(reader.readyState === 2) {
                    imageField.src = reader.result;
                    imageField.style.display = "block";
                }
            }
            if(event.target.files[0]) { reader.readAsDataURL(event.target.files[0]); }
        }
    </script>
</body>
</html>