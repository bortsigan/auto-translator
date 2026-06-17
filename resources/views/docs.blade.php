<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>DigitalTolk TMS – Docs</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css" />
    <style>body { margin: 0; }</style>
</head>
<body>
    <div id="swagger"></div>
    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js" crossorigin></script>
    <script>
        window.addEventListener('load', function () {
            SwaggerUIBundle({
                url: '{{ route('openapi.spec') }}',
                dom_id: '#swagger',
                deepLinking: true,
                presets: [SwaggerUIBundle.presets.apis],
            });
        });
    </script>
</body>
</html>
