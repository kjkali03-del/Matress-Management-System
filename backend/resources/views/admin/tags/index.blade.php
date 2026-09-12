@extends('layouts.admin')

@section('title', 'Tags | Wonder Godoro Point')

@section('content')

<header class="dashboard-topbar">
    <div>
        <p class="auth-kicker">Wonder Godoro Point</p>

        <h1>Customer Tags</h1>
    </div>

    <a
        href="{{ route('admin.customers.index') }}"
        style="
            text-decoration: none;
            font-weight: 600;
        "
    >
        &larr; Customers
    </a>
</header>

@if (session('success'))
    <div
        role="status"
        style="
            margin-bottom: 24px;
            padding: 14px 16px;
            border-radius: 10px;
            background: #eef8f0;
            border: 1px solid #cde8d2;
        "
    >
        {{ session('success') }}
    </div>
@endif

@if ($errors->any())
    <div
        role="alert"
        style="
            margin-bottom: 24px;
            padding: 14px 16px;
            border-radius: 10px;
            background: #fff3f3;
            border: 1px solid #f0cccc;
        "
    >
        <strong>Please correct the following:</strong>

        <ul style="margin: 8px 0 0 20px;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<section
    class="dashboard-welcome"
    aria-labelledby="tags-heading"
>
    <div>
        <p class="dashboard-section-kicker">
            Customer segmentation
        </p>

        <h2 id="tags-heading">
            Organize customers by relationship and sales stage.
        </h2>

        <p>
            Create simple tags such as Hot Lead, Follow Up, VIP or Won
            and attach them to customer profiles.
        </p>
    </div>
</section>

<section
    class="dashboard-module-section"
    aria-labelledby="create-tag-heading"
>
    <div class="dashboard-section-heading">
        <div>
            <p class="dashboard-section-kicker">
                Tag management
            </p>

            <h2 id="create-tag-heading">
                Create New Tag
            </h2>
        </div>

        <span class="dashboard-module-count">
            {{ $tags->count() }} tags
        </span>
    </div>

    <form
        method="POST"
        action="{{ route('admin.tags.store') }}"
        style="
            display: grid;
            grid-template-columns: minmax(0, 1fr) 160px auto;
            gap: 12px;
            align-items: end;
            max-width: 900px;
        "
    >
        @csrf

        <div>
            <label
                for="name"
                style="
                    display: block;
                    margin-bottom: 8px;
                    font-weight: 600;
                "
            >
                Tag Name
            </label>

            <input
                id="name"
                type="text"
                name="name"
                value="{{ old('name') }}"
                placeholder="e.g. Hot Lead"
                required
                style="
                    width: 100%;
                    min-height: 48px;
                    padding: 0 14px;
                    border: 1px solid #d9d9d9;
                    border-radius: 10px;
                    font: inherit;
                    background: #fff;
                "
            >
        </div>

        <div>
            <label
                for="color"
                style="
                    display: block;
                    margin-bottom: 8px;
                    font-weight: 600;
                "
            >
                Color
            </label>

            <input
                id="color"
                type="color"
                name="color"
                value="{{ old('color', '#D4AF37') }}"
                style="
                    width: 100%;
                    height: 48px;
                    padding: 4px;
                    border: 1px solid #d9d9d9;
                    border-radius: 10px;
                    background: #fff;
                    cursor: pointer;
                "
            >
        </div>

        <button
            type="submit"
            class="dashboard-primary-action"
            style="
                border: 0;
                cursor: pointer;
                min-height: 48px;
            "
        >
            Create Tag
            <span aria-hidden="true">&rarr;</span>
        </button>
    </form>
</section>

<section
    class="dashboard-module-section"
    aria-labelledby="tag-list-heading"
>
    <div class="dashboard-section-heading">
        <div>
            <p class="dashboard-section-kicker">
                Existing tags
            </p>

            <h2 id="tag-list-heading">
                All Tags
            </h2>
        </div>
    </div>

    @if ($tags->isNotEmpty())

        <div
            style="
                display: grid;
                grid-template-columns: repeat(
                    auto-fill,
                    minmax(280px, 1fr)
                );
                gap: 16px;
            "
        >
            @foreach ($tags as $tag)

                <article
                    style="
                        padding: 20px;
                        border: 1px solid #e7e7e7;
                        border-radius: 14px;
                        background: #fff;
                    "
                >
                    <div
                        style="
                            display: flex;
                            align-items: center;
                            justify-content: space-between;
                            gap: 16px;
                        "
                    >
                        <span
                            style="
                                display: inline-flex;
                                align-items: center;
                                gap: 9px;
                                font-weight: 700;
                            "
                        >
                            <span
                                aria-hidden="true"
                                style="
                                    width: 13px;
                                    height: 13px;
                                    border-radius: 50%;
                                    background: {{ $tag->color ?: '#d9d9d9' }};
                                    display: inline-block;
                                "
                            ></span>

                            {{ $tag->name }}
                        </span>

                        <span
                            style="
                                font-size: 13px;
                                opacity: .65;
                            "
                        >
                            {{ $tag->customers_count }}
                            customer{{ $tag->customers_count === 1 ? '' : 's' }}
                        </span>
                    </div>

                    <div
                        style="
                            display: flex;
                            gap: 10px;
                            margin-top: 18px;
                        "
                    >
                        <form
                            method="POST"
                            action="{{ route('admin.tags.update', $tag) }}"
                            style="
                                display: flex;
                                flex: 1;
                                gap: 8px;
                            "
                        >
                            @csrf
                            @method('PATCH')

                            <input
                                type="text"
                                name="name"
                                value="{{ $tag->name }}"
                                required
                                aria-label="Tag name"
                                style="
                                    flex: 1;
                                    min-width: 0;
                                    min-height: 40px;
                                    padding: 0 10px;
                                    border: 1px solid #d9d9d9;
                                    border-radius: 8px;
                                    font: inherit;
                                "
                            >

                            <input
                                type="color"
                                name="color"
                                value="{{ $tag->color ?: '#D4AF37' }}"
                                aria-label="Tag color"
                                style="
                                    width: 44px;
                                    height: 40px;
                                    padding: 2px;
                                    border: 1px solid #d9d9d9;
                                    border-radius: 8px;
                                    cursor: pointer;
                                "
                            >

                            <button
                                type="submit"
                                style="
                                    min-height: 40px;
                                    padding: 0 12px;
                                    border: 1px solid #d9d9d9;
                                    border-radius: 8px;
                                    background: #fff;
                                    cursor: pointer;
                                    font: inherit;
                                    font-weight: 600;
                                "
                            >
                                Save
                            </button>
                        </form>

                        <form
                            method="POST"
                            action="{{ route('admin.tags.destroy', $tag) }}"
                            onsubmit="return confirm('Delete this tag?');"
                        >
                            @csrf
                            @method('DELETE')

                            <button
                                type="submit"
                                aria-label="Delete {{ $tag->name }}"
                                style="
                                    min-height: 40px;
                                    padding: 0 12px;
                                    border: 1px solid #e1caca;
                                    border-radius: 8px;
                                    background: #fff;
                                    cursor: pointer;
                                    font: inherit;
                                    font-weight: 600;
                                "
                            >
                                Delete
                            </button>
                        </form>
                    </div>
                </article>

            @endforeach
        </div>

    @else

        <div
            style="
                padding: 48px 24px;
                text-align: center;
                background: #fff;
                border: 1px solid #e7e7e7;
                border-radius: 14px;
            "
        >
            <h3>No tags yet</h3>

            <p style="opacity: .7;">
                Create your first customer tag above.
            </p>
        </div>

    @endif

</section>

@endsection