@extends('layouts.app')
@section('title','Novo Curso')

@section('content')
    <section class="mx-auto container-page px-4 py-10 max-w-5xl">
        <div class="card p-6">
            <h1 class="text-xl font-bold mb-4">Novo Curso</h1>

            <form id="cursoForm" method="POST" action="{{ route('prof.cursos.store') }}" enctype="multipart/form-data">
                @csrf
                @include('prof.cursos._form', ['mode' => 'create'])
            </form>
        </div>
    </section>
@endsection
