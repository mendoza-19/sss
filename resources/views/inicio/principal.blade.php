@include('includes.header')

<body class="d-flex flex-column" style="height: 100vh; overflow: hidden;">

    @include('includes.topbar')

    <div class="d-flex flex-grow-1" style="min-height: 0;">

        @include('includes.sidebar')

        <main
            class="flex-grow-1 p-4"
            style="background-color:#F8F9FA; overflow-y: auto; overflow-x: hidden;">

            @yield('content')

        </main>

    </div>

    @include('includes.footer')

</body>
</html>