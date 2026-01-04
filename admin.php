<?php
session_start();
$storage = 'data.json';
if (!file_exists($storage)) file_put_contents($storage, json_encode(["config"=>["gemini_api_key"=>"","max_clips"=>3,"password"=>"RF2025"],"stats"=>["total_processed"=>0,"total_clips"=>0],"logs"=>[]]));
$data = json_decode(file_get_contents($storage), true);

if (isset($_POST['login'])) {
    if ($_POST['password'] === $data['config']['password']) $_SESSION['auth_clipper'] = true;
    else $error = "Password Salah!";
}
if (isset($_GET['logout'])) { session_destroy(); header("Location: admin.php"); exit; }
$is_auth = $_SESSION['auth_clipper'] ?? false;

if ($is_auth && isset($_POST['save_config'])) {
    $data['config']['gemini_api_key'] = $_POST['api_key'];
    $data['config']['max_clips'] = (int)$_POST['max_clips'];
    file_put_contents($storage, json_encode($data, JSON_PRETTY_PRINT));
    $msg = "Konfigurasi Berhasil Disimpan!";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><title>Admin Clipper AI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-white font-sans antialiased">
    <?php if (!$is_auth): ?>
    <div class="min-h-screen flex items-center justify-center p-6">
        <div class="w-full max-w-md bg-slate-900 border border-slate-800 p-10 rounded-[2.5rem] shadow-2xl">
            <h1 class="text-3xl font-black mb-8 text-center italic tracking-tighter">CLIPPER<span class="text-blue-500">.ADMIN</span></h1>
            <?php if (isset($error)): ?><div class="bg-red-500/10 border border-red-500/50 text-red-400 p-4 rounded-2xl mb-6 text-sm text-center"><?= $error ?></div><?php endif; ?>
            <form method="POST" class="space-y-4">
                <input type="password" name="password" placeholder="Masukkan Password" class="w-full bg-slate-800 border border-slate-700 p-4 rounded-2xl focus:ring-2 focus:ring-blue-500 outline-none transition">
                <button type="submit" name="login" class="w-full bg-blue-600 hover:bg-blue-700 p-4 rounded-2xl font-bold transition shadow-lg shadow-blue-900/20">Akses Sistem</button>
            </form>
        </div>
    </div>
    <?php else: ?>
    <div class="container mx-auto max-w-4xl py-12 px-6">
        <div class="flex justify-between items-center mb-12">
            <h1 class="text-3xl font-black italic">PENGATURAN <span class="text-blue-500">PRODUKSI</span></h1>
            <a href="?logout=1" class="text-xs bg-slate-800 hover:bg-slate-700 px-6 py-2 rounded-full font-bold">Log Keluar</a>
        </div>
        <?php if (isset($msg)): ?><div class="bg-emerald-500/10 border border-emerald-500/50 text-emerald-400 p-4 rounded-2xl mb-8 text-sm"><?= $msg ?></div><?php endif; ?>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="bg-slate-900 border border-slate-800 p-8 rounded-[2rem]">
                <h2 class="text-xl font-bold mb-6">Konfigurasi API</h2>
                <form method="POST" class="space-y-6">
                    <div>
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest block mb-2">Gemini API Key</label>
                        <input type="text" name="api_key" value="<?= htmlspecialchars($data['config']['gemini_api_key']) ?>" class="w-full bg-slate-800 border border-slate-700 p-4 rounded-2xl font-mono text-sm">
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest block mb-2">Maksimal Klip</label>
                        <input type="number" name="max_clips" value="<?= $data['config']['max_clips'] ?>" class="w-full bg-slate-800 border border-slate-700 p-4 rounded-2xl">
                    </div>
                    <button type="submit" name="save_config" class="w-full bg-white text-black p-4 rounded-2xl font-black uppercase text-xs tracking-widest hover:bg-blue-500 hover:text-white transition">Simpan Perubahan</button>
                </form>
            </div>
            <div class="bg-slate-900 border border-slate-800 p-8 rounded-[2rem]">
                <h2 class="text-xl font-bold mb-6 text-purple-400">Statistik Global</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-slate-800 p-4 rounded-2xl">
                        <div class="text-3xl font-black text-blue-500"><?= $data['stats']['total_processed'] ?></div>
                        <div class="text-[10px] font-bold text-slate-500 uppercase">Video</div>
                    </div>
                    <div class="bg-slate-800 p-4 rounded-2xl">
                        <div class="text-3xl font-black text-purple-500"><?= $data['stats']['total_clips'] ?></div>
                        <div class="text-[10px] font-bold text-slate-500 uppercase">Klip AI</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</body>
</html>
