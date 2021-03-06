<style>
    .banner {
        background-image: url("<?php echo base_url().'assets/img/admin.jpg'; ?>");
    }

    .nav-pills > li.active > a, .nav-pills > li.active > a:focus {
        color: white;
        background-color: #129FEA;
    }

    .nav-pills > li.active > a:hover {
        background-color: #a07ab1;
        color:white;
    }

    a {
        color: #129FEA;
        -webkit-transition: all .35s;
        -moz-transition: all .35s;
        transition: all .35s;
    }

    a:hover,
    a:focus {
        color: #a07ab1;
    }

    .btn-primary {
        border-color: #129FEA;
        color: #fff;
        background-color: #129FEA;
        -webkit-transition: all .35s;
        -moz-transition: all .35s;
        transition: all .35s;
    }

    .btn-primary:hover,
    .btn-primary:focus,
    .btn-primary.focus,
    .btn-primary:active,
    .btn-primary.active,
    .open > .dropdown-toggle.btn-primary {
        border-color: #a07ab1;
        color: #fff;
        background-color: #a07ab1;
    }

    .pagination > .active > a, .pagination > .active > a:focus, .pagination > .active > a:hover,
    .pagination > .active > span, .pagination > .active > span:focus, .pagination > .active > span:hover {
        z-index: 2;
        color: #fff;
        cursor: default;
        background-color: #129FEA;
        border-color: #129FEA
    }

    .pagination > li > a, .pagination > li > span {
        position: relative;
        float: left;
        padding: 6px 12px;
        margin-left: -1px;
        line-height: 1.42857143;
        color: #129FEA;
        text-decoration: none;
        background-color: #fff;
        border: 1px solid #ddd
    }

    hr {
        max-width: 50px;
        border-color: #129FEA;
        border-width: 3px;
    }

    .btn-default {
        border-color: #129FEA;
        color: #222;
        background-color: #129FEA;
        -webkit-transition: all .35s;
        -moz-transition: all .35s;
        transition: all .35s;
    }

    .btn-default:hover,
    .btn-default:focus,
    .btn-default.focus,
    .btn-default:active,
    .btn-default.active,
    .open > .dropdown-toggle.btn-default {
        border-color: #129FEA;
        color: #222;
        background-color: #129FEA;
    }

</style>

<script src="<?php echo base_url('assets/js/jquery.dataTables.min.js'); ?>"></script>
<script src="<?php echo base_url('assets/js/admin/acciones.js'); ?>"></script>

<link rel="stylesheet" href="<?php echo base_url('assets/css/jquery.dataTables.min.css'); ?>">
<div class="container-fluid">

    <div class="highlight" align="center">
        <div class="col-lg-12 banner">
            <br>
            <br>
            <br>
            <br>
            <h1 class="page-header" style="font-size: 65px; color: white;">
                Proyectos
                <br>
                <br>
            </h1>
        </div>
    </div>

    <div class="col-md-3">

        <ul class="nav nav-pills nav-stacked" >
            <li role="presentation" ><a href="<?php echo base_url(); ?>statistics">Estadísticas</a></li>
            <li role="presentation" ><a href="<?php echo base_url(); ?>reports">Reportes custom</a></li>
            <li role="presentation" class="active"><a href="<?php echo base_url(); ?>admin">Todos los proyectos</a></li>
            <li role="presentation" ><a href="<?php echo base_url(); ?>users">Usuarios</a></li>
            <li role="presentation" ><a href="<?php echo base_url(); ?>newletterempr">Newsletter Emprendedor</a></li>
        </ul>

    </div>

    <div class="col-md-9">

        <div class="panel panel-default">
            <div class="panel-body">

                <table id="todosLosProyectos"  class="table table-striped">

                    <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Código de emprendedor</th>
                        <th>Apellido, nombre</th>
                        <th>Estado</th>
                        <th>Fecha Alta</th>
                        <th>Acciones</th>
                    </tr>
                    </thead>

                    <tbody>

                    <?php

                    foreach($proyectos as $p)
                    {
                        echo '<tr>

                        <td><a target="_blank" href="'.base_url().'descripcion/'.$p->ID_proyecto.'" >'.$p->proy_nombre.'</a></td>
                        <td>'.$p->user_id.'</td>
                        <td>'.$p->apellido.', '.$p->nombre.'</td>
                        <td id="nombreEstado'.$p->ID_proyecto.'">'.$p->nombre_estad.'</td>
                        <td>'.$p->fecha_alta.'</td>
                        <td>';

                            switch ($p->nombre_estad)
                            {
                                case "solicitado":
                                        echo '<button type="button" title="Aceptar" class="btn btn-default aceptar" id="btnAceptar'.$p->ID_proyecto.'" value="'.$p->ID_proyecto.'"><span class="glyphicon glyphicon-ok"></span></button>
                                            <button type="button" title="Rechazar" class="btn btn-default rechazar" id="btnRechazar'.$p->ID_proyecto.'" value="'.$p->ID_proyecto.'"> <span class="glyphicon glyphicon-remove"></span></button>';
                                    break;
                                case "activo":
                                        echo '<button type="button" title="Clausurar" class="btn btn-default clausurar" id="btnClausurar'.$p->ID_proyecto.'" value="'.$p->ID_proyecto.'"> <span class="glyphicon glyphicon-ban-circle"></span></button></td>';        
                                    break;
                                case "clausurado":
                                        echo "Sin acciones disponibles";
                                    break;
                                case "finalizado":
                                    echo "Sin acciones disponibles";
                                    break;
                            }
                        echo '</tr>';

                    }

                    ?>

                    </tbody>

                </table>

            </div>
        </div>

    </div>

</div>
<script>

    $(document).ready(function() {
        $('#todosLosProyectos').DataTable( {
            initComplete: function () {
                this.api().column(3).every( function () {
                    var column = this;
                    var select = $('<select><option value="">Todos</option></select>')
                        .appendTo( $(column.header()) )
                        .on( 'change', function () {
                            var val = $.fn.dataTable.util.escapeRegex(
                                $(this).val()
                            );

                            column
                                .search( val ? '^'+val+'$' : '', true, false )
                                .draw();
                        } );

                    column.data().unique().sort().each( function ( d, j ) {
                        select.append( '<option value="'+d+'">'+d+'</option>' )
                    } );
                } );
            }
        } );
    } );

</script>
