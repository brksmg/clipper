<?php
session_start();
$storage = 'data.json';
$pass = "RF2025";
$data = json_decode(file_get_contents($storage), true);

if (isset($_POST['login'])) {
    if ($_POST['password'] === $pass) $_SESSION['auth'] = true;
    else $error = "Password Salah!";
}

if (isset($_GET['logout'])) { session_destroy(); header("Location: admin.php"); exit; }
$is_auth = $_SESSION['auth'] ?? false;

if ($is_auth && isset($_POST['save'])) {
    $data['config']['gemini_api_key'] = $_POST['api_key'];
    $data['config']['max_clips'] = (int)$_POST['max_clips'];
    file_put_contents($storage, json_encode($data, JSON_PRETTY_PRINT));
    $msg = "Konfigurasi Disimpan!";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><title>Admin - Clipper AI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-white font-sans">
    <?php if (!$is_auth): ?>
    <div class="h-screen flex items-center justify-center">
        <form method="POST" class="bg-slate-900 p-8 rounded-3xl border border-slate-800 w-96 shadow-2xl">
            <h2 class="text-2xl font-bold mb-6 text-center italic">CLIPPER<span class="text-blue-500">.ADMIN</span></h2>
            <input type="password" name="password" placeholder="Password Admin" class="w-full bg-slate-800 border border-slate-700 p-4 rounded-2xl mb-4 outline-none focus:ring-2 focus:ring-blue-500">
            <button name="login" class="w-full bg-blue-600 p-4 rounded-2xl font-bold hover:bg-blue-700 transition">Masuk</button>
        </form>
    </div>
    <?php else: ?>
    <div class="max-w-4xl mx-auto py-12 px-6">
        <div class="flex justify-between mb-8">
            <h1 class="text-3xl font-black italic">PENGATURAN <span class="text-blue-500">SISTEM</span></h1>
            <a href="?logout=1" class="bg-red-900/20 text-red-500 px-4 py-2 rounded-xl text-xs font-bold">Logout</a>
        </div>
        <?php if(isset($msg)): ?><div class="bg-emerald-500/10 border border-emerald-500 text-emerald-500 p-4 rounded-2xl mb-6"><?= $msg ?></div><?php endif; ?>
        <form method="POST" class="bg-slate-900 p-8 rounded-3xl border border-slate-800 space-y-6">
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Gemini API Key</label>
                <input type="text" name="api_key" value="<?= $data['config']['gemini_api_key'] ?>" class="w-full bg-slate-800 border border-slate-700 p-4 rounded-2xl font-mono text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Maksimal Klip Per Video</label>
                <input type="number" name="max_clips" value="<?= $data['config']['max_clips'] ?>" class="w-full bg-slate-800 border border-slate-700 p-4 rounded-2xl">
            </div>
            <button name="save" class="w-full bg-blue-600 p-4 rounded-2xl font-bold">Simpan Perubahan</button>
        </form>
    </div>
    <?php endif; ?>
</body>
</html>
