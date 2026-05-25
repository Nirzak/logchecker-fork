<?php

// Handle API POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/../vendor/autoload.php';
    use OrpheusNET\Logchecker\Logchecker;

    header('Content-Type: application/json');

    if (!isset($_FILES['log']) || $_FILES['log']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => 'No log file uploaded or upload error.']);
        exit;
    }

    $file = $_FILES['log']['tmp_name'];

    try {
        $logchecker = new Logchecker();
        $logchecker->newFile($file);
        $logchecker->parse();

        $response = [
            "ripper"   => $logchecker->getRipper(),
            "version"  => $logchecker->getRipperVersion(),
            "language" => $logchecker->getLanguage(),
            "combined" => $logchecker->isCombinedLog(),
            "score"    => $logchecker->getScore(),
            "checksum" => $logchecker->getChecksumState(),
            "details"  => $logchecker->getDetails(),
        ];

        echo json_encode($response);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// Serve the beautiful UI for GET requests
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logchecker | Premium Log Analysis</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #0f172a;
            --glass-bg: rgba(30, 41, 59, 0.7);
            --glass-border: rgba(255, 255, 255, 0.1);
            --primary: #3b82f6;
            --primary-glow: rgba(59, 130, 246, 0.5);
            --success: #10b981;
            --danger: #ef4444;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
        }

        body {
            background: var(--bg-color);
            background-image: 
                radial-gradient(circle at 15% 50%, rgba(59, 130, 246, 0.15), transparent 25%),
                radial-gradient(circle at 85% 30%, rgba(139, 92, 246, 0.15), transparent 25%);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            overflow-x: hidden;
        }

        .container {
            width: 100%;
            max-width: 800px;
            z-index: 10;
        }

        header {
            text-align: center;
            margin-bottom: 3rem;
            animation: fadeInDown 0.8s ease-out;
        }

        h1 {
            font-size: 3.5rem;
            font-weight: 800;
            letter-spacing: -1px;
            background: linear-gradient(135deg, #60a5fa, #a78bfa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        p.subtitle {
            color: var(--text-muted);
            font-size: 1.1rem;
            font-weight: 300;
        }

        .upload-card {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 3rem;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            animation: fadeInUp 0.8s ease-out;
            position: relative;
            overflow: hidden;
        }

        .upload-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 50px -12px var(--primary-glow);
        }

        .drop-zone {
            border: 2px dashed var(--primary);
            border-radius: 16px;
            padding: 4rem 2rem;
            cursor: pointer;
            transition: all 0.3s ease;
            background: rgba(59, 130, 246, 0.05);
        }

        .drop-zone:hover, .drop-zone.dragover {
            background: rgba(59, 130, 246, 0.15);
            border-color: #60a5fa;
            transform: scale(1.02);
        }

        .drop-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #60a5fa;
        }

        .drop-text {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .drop-hint {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        input[type="file"] {
            display: none;
        }

        /* Results Section */
        .results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
            opacity: 0;
            display: none;
            transform: translateY(20px);
            transition: all 0.5s ease;
        }

        .results-grid.visible {
            opacity: 1;
            display: grid;
            transform: translateY(0);
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 1.5rem;
            text-align: left;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--primary);
        }

        .stat-card.score-card::before { background: var(--success); }
        .stat-card.error-card::before { background: var(--danger); }

        .stat-label {
            color: var(--text-muted);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 800;
        }

        .details-list {
            margin-top: 2rem;
            text-align: left;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid var(--glass-border);
        }

        .details-list h3 {
            margin-bottom: 1rem;
            font-size: 1.2rem;
            color: #f8fafc;
        }

        .details-list ul {
            list-style: none;
        }

        .details-list li {
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            color: #cbd5e1;
            display: flex;
            align-items: center;
        }
        
        .details-list li::before {
            content: '•';
            color: var(--danger);
            font-size: 1.5rem;
            margin-right: 0.75rem;
        }

        .details-list li:last-child {
            border-bottom: none;
        }

        /* Animations */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .loader {
            display: none;
            width: 48px;
            height: 48px;
            border: 4px solid var(--glass-border);
            border-bottom-color: var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 2rem auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

    <div class="container">
        <header>
            <h1>Logchecker</h1>
            <p class="subtitle">Premium CD Rip Analysis powered by OrpheusNET</p>
        </header>

        <div class="upload-card">
            <div class="drop-zone" id="dropZone" onclick="document.getElementById('fileInput').click()">
                <div class="drop-icon">📄</div>
                <div class="drop-text">Drop your .log file here</div>
                <div class="drop-hint">or click to browse from your computer</div>
                <input type="file" id="fileInput" accept=".log,.txt">
            </div>

            <div class="loader" id="loader"></div>

            <div class="results-grid" id="resultsGrid">
                <div class="stat-card score-card">
                    <div class="stat-label">Final Score</div>
                    <div class="stat-value" id="resScore">--</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Ripper</div>
                    <div class="stat-value" id="resRipper">--</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Checksum</div>
                    <div class="stat-value" id="resChecksum" style="font-size: 1.2rem; align-self: center;">--</div>
                </div>
            </div>

            <div class="details-list" id="detailsList" style="display: none;">
                <h3>Deductions & Warnings</h3>
                <ul id="detailsUl"></ul>
            </div>
        </div>
    </div>

    <script>
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const loader = document.getElementById('loader');
        const resultsGrid = document.getElementById('resultsGrid');
        const detailsList = document.getElementById('detailsList');

        // Drag & Drop visual feedback
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => dropZone.classList.add('dragover'), false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => dropZone.classList.remove('dragover'), false);
        });

        // Handle file drop
        dropZone.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            if (dt.files && dt.files.length > 0) {
                handleFile(dt.files[0]);
            }
        });

        // Handle file browse
        fileInput.addEventListener('change', function() {
            if (this.files && this.files.length > 0) {
                handleFile(this.files[0]);
            }
        });

        function handleFile(file) {
            // Reset UI
            dropZone.style.display = 'none';
            resultsGrid.classList.remove('visible');
            detailsList.style.display = 'none';
            loader.style.display = 'block';

            const formData = new FormData();
            formData.append('log', file);

            fetch('/', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                loader.style.display = 'none';
                
                if (data.error) {
                    alert('Error: ' + data.error);
                    dropZone.style.display = 'block';
                    return;
                }

                // Populate results
                document.getElementById('resScore').innerText = data.score + '/100';
                document.getElementById('resScore').style.color = data.score === 100 ? 'var(--success)' : (data.score > 80 ? '#fbbf24' : 'var(--danger)');
                
                document.getElementById('resRipper').innerText = data.ripper + ' ' + (data.version || '');
                document.getElementById('resChecksum').innerText = data.checksum.replace('_', ' ').toUpperCase();

                // Details
                const ul = document.getElementById('detailsUl');
                ul.innerHTML = '';
                if (data.details && data.details.length > 0) {
                    data.details.forEach(detail => {
                        const li = document.createElement('li');
                        li.innerText = detail;
                        ul.appendChild(li);
                    });
                    detailsList.style.display = 'block';
                }

                resultsGrid.classList.add('visible');
                
                // Allow another upload
                setTimeout(() => {
                    dropZone.style.display = 'block';
                    dropZone.querySelector('.drop-text').innerText = 'Analyze another log';
                }, 1000);
            })
            .catch(error => {
                loader.style.display = 'none';
                dropZone.style.display = 'block';
                alert('An error occurred while uploading the file.');
                console.error(error);
            });
        }
    </script>
</body>
</html>
