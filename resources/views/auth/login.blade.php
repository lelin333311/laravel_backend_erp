<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-slate-100 min-h-screen flex items-center justify-center">
        <div class="w-full max-w-md bg-white rounded-xl shadow-md p-8">
            <h1 class="text-2xl font-bold text-slate-800 mb-6 text-center">Login</h1>

            @if ($errors->any())
                <div class="mb-4 rounded bg-red-50 border border-red-200 p-3 text-sm text-red-700">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('login.submit') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                    <input type="password" name="password" required class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                </div>

                <div class="flex items-center justify-between text-sm">
                    <label class="inline-flex items-center gap-2 text-slate-600">
                        <input type="checkbox" name="remember" value="1">
                        Remember me
                    </label>
                    <a href="{{ route('register') }}" class="text-indigo-600 hover:underline">Create account</a>
                </div>

                <button type="submit" class="w-full bg-indigo-600 text-white py-2.5 rounded-lg font-medium hover:bg-indigo-700 transition">
                    Login
                </button>
            </form>
        </div>
    </body>
</html>
