$(document).ready(function(){

    $('body').on('click','.inhabilitar', function(){
        if(confirm('Desea inhabilitar este usuario?')) {
            var idUsuario = $(this).attr('value');
            $.ajax({
                url: 'admin/inhabilitarusuario',
                data: {idUsuario: idUsuario},
                method: "get",
                dataType: "json",
                success: function (e) {
                    if(e.status == true){
                        pathArray = location.href.split( '/' );
                        protocol = pathArray[0];
                        host = pathArray[2];
                        url = protocol + '//' + host + '/users';
                        window.location.href = url;
                    }
                    else
                    {
                        alert(e.message);
                    }
                }
            });
        }
    });

});