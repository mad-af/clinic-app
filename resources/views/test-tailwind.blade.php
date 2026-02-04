<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Tailwind CSS v3</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-8">
        <h1 class="text-4xl font-bold text-blue-600 mb-4">Tailwind CSS v3 Test</h1>
        <p class="text-gray-700 mb-6">This is a test page to verify Tailwind CSS v3 is working properly.</p>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold text-gray-800 mb-2">Card 1</h2>
                <p class="text-gray-600">This card uses Tailwind v3 utility classes.</p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold text-gray-800 mb-2">Card 2</h2>
                <p class="text-gray-600">Grid layout with responsive design.</p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold text-gray-800 mb-2">Card 3</h2>
                <p class="text-gray-600">Hover effects and transitions.</p>
            </div>
        </div>
        
        <button class="mt-6 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition duration-300">
            Test Button
        </button>
    </div>
</body>
</html>