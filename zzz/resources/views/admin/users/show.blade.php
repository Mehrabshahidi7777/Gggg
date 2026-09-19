@extends('admin.layouts.app') @section('content')<div class="bg-white p-6 rounded-xl"><h1 class="text-2xl font-black">{{ $user->name }}</h1><p class="mt-3">{{ $user->email }}</p></div>@endsection
