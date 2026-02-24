<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Advanced API Guide - API Docs</title>
    <style>
        body { margin: 0; }
        #redoc { min-height: 100vh; }
    </style>
</head>
<body>
<div id="redoc"></div>
<script src="https://cdn.redoc.ly/redoc/latest/bundles/redoc.standalone.js"></script>
<script>
    Redoc.init('/openapi.yaml', {}, document.getElementById('redoc'));
</script>
</body>
</html>
