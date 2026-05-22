@extends('layouts.app')
@section('title','Editar Curso')

@section('content')
    <section class="mx-auto container-page px-4 py-10 max-w-5xl">
        <div class="card p-6">
            <h1 class="text-xl font-bold mb-4">Editar Curso</h1>

            <form id="cursoForm" method="POST" action="{{ route('prof.cursos.update', $curso->id) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT') {{-- ou PATCH --}}
                @include('prof.cursos._form', ['mode' => 'edit'])
            </form>
        </div>
    </section>
@endsection
