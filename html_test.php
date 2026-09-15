<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Render User HTML</title>
</head>
<body>
    <form id="htmlForm">
        <textarea name="html_content" rows="10" cols="50" placeholder="Enter your HTML here..."></textarea>
        <br>
        <button type="submit">Render HTML</button>
    </form>
    <div id="preview">
        <iframe id="htmlPreview" sandbox="allow-same-origin" width="100%" height="400"></iframe>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#htmlForm').on('submit', function(e) {
                e.preventDefault();
                let htmlContent = $('textarea[name="html_content"]').val();

                $.ajax({
                    url: 'render_html.php',
                    type: 'POST',
                    data: { html_content: htmlContent },
                    success: function(response) {
                        let blob = new Blob([response], { type: 'text/html' });
                        let url = URL.createObjectURL(blob);
                        $('#htmlPreview').attr('src', url);
                    },
                    error: function() {
                        alert('Error processing HTML. Please try again.');
                    }
                });
            });
        });
    </script>
</body>
</html>
