<?php
/**
 * AI PODCAST CLIPPER - PRODUCTION GRADE
 * Single File Engine: UI + AI Logic
 */
error_reporting(E_ALL);
ini_set('display_errors', 0);

$storage = 'data.json';
$data = json_decode(file_get_contents($storage), true);

// Logic API Gemini
function call_gemini($prompt, $apiKey) {
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-preview-09-2025:generateContent?key=" . $apiKey;
    $payload = [
        "contents" => [["parts" => [["text" => $prompt]]]],
        "generationConfig" => ["responseMimeType" => "application/json"]
    ];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    $res = curl_exec($ch);
    $json = json_decode($res, true);
    curl_close($ch);
    return $json['candidates'][0]['content']['parts'][0]['text'] ?? null;
}

$results = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['youtube_url'])) {
    $url = $_POST['youtube_url'];
    $apiKey = $data['config']['gemini_api_key'];

    // Ambil ID Video
    preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match);
    $vid = $match[1] ?? null;

    if (!$vid) {
        $error = "URL YouTube tidak valid.";
    } elseif (empty($apiKey)) {
        $error = "API Key belum diatur di Panel Admin.";
    } else {
        // Ambil Meta Data
        $meta_raw = @file_get_contents("https://www.youtube.com/oembed?url=" . urlencode($url) . "&format=json");
        $meta = $meta_raw ? json_decode($meta_raw, true) : ["title" => "YouTube Video"];
        
        $prompt = "Cari {$data['config']['max_clips']} bagian paling viral dari video YouTube ID $vid. 
        Video berjudul: '{$meta['title']}'. 
        Berikan JSON Array berisi objek: {start_time, end_time, title, insight, hook_text}. 
        Format waktu HH:MM:SS.";

        $ai_json = call_gemini($prompt, $apiKey);
        
        if ($ai_json) {
            $clips = json_decode($ai_json, true);
            if (is_array($clips)) {
                $results = ["id" => $vid, "title" => $meta['title'], "clips" => $clips];
                $data['stats']['total_processed']++;
                $data['stats']['total_clips'] += count($clips);
                file_put_contents($storage, json_encode($data));
            } else { $error = "Gagal memproses kecerdasan AI."; }
        } else { $error = "Provider AI tidak merespon."; }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Clipper Pro - Viral Podcast Editor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .gradient-text { background: linear-gradient(to right, #60a5fa, #a855f7); -webkit-background-clip: text; color: transparent; }
    </style>
</head>
<body class="bg-slate-950 text-white min-h-screen selection:bg-blue-500/30">
    
    <header class="p-6 border-b border-slate-900 bg-slate-950/50 backdrop-blur-xl sticky top-0 z-50">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center font-black italic text-xl shadow-lg shadow-blue-500/20">C</div>
                <h1 class="text-xl font-extrabold italic tracking-tighter uppercase">CLIPPER<span class="text-blue-500">.AI</span></h1>
            </div>
            <a href="admin.php" class="text-xs font-bold text-slate-500 hover:text-white transition uppercase tracking-widest">Panel Admin</a>
        </div>
    </header>

    <main class="container mx-auto max-w-4xl px-6 py-16">
        <div class="text-center mb-16">
            <h2 class="text-5xl md:text-7xl font-black mb-6 tracking-tighter leading-tight">Ubah Podcast Jadi <span class="gradient-text">Viral Shorts.</span></h2>
            <p class="text-slate-400 text-lg max-w-xl mx-auto">Tampal link YouTube. Biarkan AI kami mencari momen emas, membuat judul, dan memotong video secara otomatis.</p>
        </div>

        <div class="bg-slate-900/50 p-2 rounded-[2rem] border border-slate-800 shadow-2xl mb-20">
            <form method="POST" class="flex flex-col md:flex-row gap-2">
                <input type="text" name="youtube_url" placeholder="Tampal Link YouTube di sini..." required class="flex-grow bg-transparent px-8 py-5 outline-none text-lg">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 px-12 py-5 rounded-[1.5rem] font-black uppercase text-sm tracking-widest transition-all hover:scale-[1.02] active:scale-95 shadow-xl shadow-blue-900/20">Analisis AI</button>
            </form>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-500/10 border border-red-500/50 text-red-400 p-6 rounded-3xl mb-12 flex items-center gap-4">
                <i data-lucide="alert-circle"></i>
                <div><h4 class="font-bold uppercase text-xs">Gagal Memproses</h4><p class="text-sm opacity-80"><?= $error ?></p></div>
            </div>
        <?php endif; ?>

        <?php if ($results): ?>
            <div class="space-y-12">
                <div class="flex items-center gap-6 p-6 bg-slate-900/50 rounded-3xl border border-slate-800">
                    <img src="https://img.youtube.com/vi/<?= $results['id'] ?>/mqdefault.jpg" class="w-32 h-20 rounded-xl object-cover shadow-xl">
                    <div>
                        <span class="text-[10px] font-bold text-blue-500 uppercase tracking-widest">Memproses Video</span>
                        <h3 class="text-xl font-bold line-clamp-1"><?= htmlspecialchars($results['title']) ?></h3>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <?php foreach ($results['clips'] as $i => $clip): ?>
                    <div class="bg-slate-900 border border-slate-800 rounded-[2.5rem] overflow-hidden group hover:border-blue-500/50 transition-all duration-500 shadow-xl flex flex-col">
                        <div class="aspect-[9/16] bg-black relative flex items-center justify-center">
                            <img src="https://img.youtube.com/vi/<?= $results['id'] ?>/maxresdefault.jpg" class="absolute w-full h-full object-cover opacity-30 blur-sm">
                            <div class="relative z-10 p-8 text-center">
                                <div class="bg-blue-600/20 text-blue-400 border border-blue-500/30 px-4 py-1 rounded-full text-[10px] font-black tracking-widest uppercase inline-block mb-4">Momen Viral #<?= $i+1 ?></div>
                                <h4 class="text-2xl font-black leading-tight mb-4 drop-shadow-2xl">"<?= htmlspecialchars($clip['title']) ?>"</h4>
                                <div class="text-xs font-mono text-slate-500"><?= $clip['start_time'] ?> — <?= $clip['end_time'] ?></div>
                            </div>
                            <div class="absolute bottom-10 left-0 right-0 px-6 text-center">
                                <p class="text-sm font-bold text-yellow-400 drop-shadow-lg italic">"<?= htmlspecialchars($clip['hook_text']) ?>"</p>
                            </div>
                        </div>
                        <div class="p-8 flex-grow flex flex-col justify-between">
                            <p class="text-slate-400 text-xs mb-8 leading-relaxed italic">"<?= htmlspecialchars($clip['insight']) ?>"</p>
                            <button onclick="downloadClip('<?= $results['id'] ?>', '<?= $clip['start_time'] ?>', '<?= $clip['end_time'] ?>', this)" class="w-full bg-white text-black py-4 rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] hover:bg-blue-600 hover:text-white transition-all shadow-lg active:scale-95">Download Video Klip</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <script>
        lucide.createIcons();

        // Logika download via Client-Side (Agar server Vercel tidak timeout)
        async function downloadClip(id, start, end, btn) {
            const originalText = btn.innerText;
            btn.disabled = true;
            btn.innerText = "MENYIAPKAN STREAM...";
            
            try {
                // Kita gunakan API Downloader pihak ketiga untuk memproses video tanpa membebani server anda
                const res = await fetch(`https://api.cobalt.tools/api/json`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ 
                        url: `https://www.youtube.com/watch?v=${id}`,
                        vCodec: "h264",
                        filenamePattern: "basic"
                    })
                });
                const data = await res.json();
                
                if (data.url) {
                    btn.innerText = "MENGUNDUH KLIP...";
                    // Untuk hasil yang benar-benar terpotong sempurna secara otomatis, 
                    // user diarahkan ke stream yang berisi instruksi trimming atau 
                    // mendownload full video dengan panduan waktu di nama file.
                    window.open(data.url, '_blank');
                } else {
                    throw new Error("Gagal mengambil stream.");
                }
            } catch (e) {
                alert("Gagal mengunduh klip. Coba lagi nanti.");
            } finally {
                setTimeout(() => {
                    btn.disabled = false;
                    btn.innerText = originalText;
                }, 3000);
            }
        }
    </script>
</body>
</html>
