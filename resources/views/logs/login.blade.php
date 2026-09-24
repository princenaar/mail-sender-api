<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mail Logs Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center px-4">
    <main class="w-full max-w-sm bg-white rounded-lg shadow p-8">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Mail Logs</h1>

        @if(!empty($error))
            <p class="mb-4 text-sm text-red-700" role="alert">{{ $error }}</p>
        @endif

        @if($alreadyAuthenticated)
            <p class="mb-4 text-sm text-gray-600">You are already signed in.</p>
            <a href="{{ route('logs.index') }}" class="text-sm font-medium text-blue-700 underline">Open mail logs</a>
        @else
            <form method="POST" action="{{ route('logs.authenticate') }}" class="space-y-4">
                @csrf
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <input id="password" name="password" type="password" required autofocus autocomplete="current-password"
                    class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                <button type="submit" class="w-full rounded-md bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">
                    Sign in
                </button>
            </form>
        @endif
    </main>
</body>
</html>
