<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Dashboard</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-slate-100 min-h-screen">
        <div class="max-w-5xl mx-auto py-10 px-4">
            <div class="bg-white shadow rounded-xl p-6">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm text-slate-500">Welcome</p>
                        <h1 class="text-3xl font-bold text-slate-800">{{ $user->name }}</h1>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600">
                            Logout
                        </button>
                    </form>
                </div>

                <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-5">
                        <p class="text-sm text-indigo-700">Email</p>
                        <p class="mt-2 font-semibold text-slate-800">{{ $user->email }}</p>
                    </div>
                    <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-5">
                        <p class="text-sm text-emerald-700">Status</p>
                        <p class="mt-2 font-semibold text-slate-800">Logged in</p>
                    </div>
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-5">
                        <p class="text-sm text-amber-700">Role</p>
                        <p class="mt-2 font-semibold text-slate-800">User</p>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
