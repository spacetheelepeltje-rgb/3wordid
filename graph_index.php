<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Graph Files</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Space+Grotesk:wght@500;600&display=swap');
        
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e2937 100%);
            color: #e2e8f0;
            margin: 0;
            padding: 40px 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .header {
            background: rgba(15, 23, 42, 0.95);
            border: 1px solid #334155;
            border-radius: 16px;
            padding: 32px;
            margin-bottom: 40px;
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 
                        0 8px 10px -6px rgb(0 0 0 / 0.1);
        }
        
        h1 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 2.5rem;
            margin: 0 0 12px 0;
            background: linear-gradient(to right, #60a5fa, #a78bfa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .description {
            color: #94a3b8;
            font-size: 1.1rem;
            line-height: 1.6;
            margin: 0;
        }
        
        .list-container {
            background: rgba(15, 23, 42, 0.95);
            border: 1px solid #334155;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 
                        0 8px 10px -6px rgb(0 0 0 / 0.1);
        }
        
        .list-header {
            background: #1e2937;
            padding: 20px 28px;
            border-bottom: 1px solid #334155;
            font-weight: 600;
            color: #cbd5e1;
            font-size: 1.1rem;
        }
        
        .file-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        .file-item {
            border-bottom: 1px solid #334155;
            transition: all 0.2s ease;
        }
        
        .file-item:last-child {
            border-bottom: none;
        }
        
        .file-item:hover {
            background: #1e2937;
        }
        
        .file-link {
            display: flex;
            align-items: center;
            padding: 20px 28px;
            color: #e2e8f0;
            text-decoration: none;
            font-size: 1.1rem;
            transition: all 0.2s ease;
        }
        
        .file-link:hover {
            color: #60a5fa;
            transform: translateX(8px);
        }
        
        .file-icon {
            width: 28px;
            height: 28px;
            background: linear-gradient(135deg, #60a5fa, #a78bfa);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 16px;
            font-size: 14px;
            font-weight: bold;
            color: white;
        }
        
        .file-name {
            flex: 1;
        }
        
        .empty-state {
            padding: 60px 28px;
            text-align: center;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Graph Files</h1>
            <p class="description">
                Browse and access all graph-related files in this directory. 
                Click on any file below to view or download it.
            </p>
        </div>

        <div class="list-container">
            <div class="list-header">
                Available Graph Files
            </div>
            
            <?php
            $files = glob('graph*');
            $files = array_filter($files, function($file) {
                return is_file($file);
            });
            
            if (!empty($files)):
            ?>
                <ul class="file-list">
                    <?php foreach ($files as $file): ?>
                        <li class="file-item">
                            <a href="<?= htmlspecialchars($file) ?>" class="file-link">
                                <div class="file-icon">📈</div>
                                <div class="file-name">
                                    <?= htmlspecialchars($file) ?>
                                </div>
                                <span style="color: #64748b; font-size: 0.9rem;">
                                    <?= date('M d, Y', filemtime($file)) ?>
                                </span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="empty-state">
                    <div style="font-size: 3rem; margin-bottom: 16px;">📭</div>
                    <h3>No graph files found</h3>
                    <p>Files starting with <strong>"graph"</strong> will appear here.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
