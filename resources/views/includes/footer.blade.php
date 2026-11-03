<footer class="text-white text-center py-3 mt-auto"
        style="background-color:#4a3728;">
    © 2026 Panadería El buen sabor
</footer>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

<script>

const btnMenu = document.getElementById('btnMenu');

if(btnMenu){

    btnMenu.addEventListener('click', function(){

        document.querySelectorAll('.texto-menu').forEach(function(elemento){
            elemento.classList.toggle('d-none');
        });

    });

}

</script>
</body>
</html>