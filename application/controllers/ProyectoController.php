<?php

class ProyectoController extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('form_validation');
        $this->load->model('proyecto');
        $this->load->model('emprendedor');
        $this->load->model('MultimediaProyecto');
        $this->load->model('rubro');
        $this->load->model('Permisos');
        $this->load->library('session');
    }
    public function index()
    {

    }
    
    public function editarProyecto()
    {
        $p = new Proyecto();
        $id = $this->uri->segment(3);
        $data['id'] = $id;
        $data['username'] = $this->session->userdata['logged_in']['username'];
        $resultProyecto = $p->getProyectoById($id);



        if (!$resultProyecto)
        {
            $error = new ErrorPropio();
            $error->Error_bd();
        }
        else
        {
            $pdf = $p->getPDFbyIdProyecto($id);
            $imgs = $p->getImgsByIdProyecto($id);
            $dt1 = $resultProyecto->fecha_alta;
            $dt2 = $resultProyecto->fecha_baja;
            
            $mm = new MultimediaProyecto();
            
            
            
            $data['proyecto'] = $resultProyecto;
            
            if($mm->containsVideo($id))
            {
                $data['video'] = true;    
            }
            else 
            {
                $data['video'] = false;
            }
            
            $data['pdf'] = $pdf;
            $data['cant_img'] = count($imgs);
            $data['imgs'] = $imgs;
            $data['fecha_alta'] = $dt1;
            $data['fecha_baja'] = $dt2;
            $this->load->view('commons/header',$data);
            $this->load->view('emprendedor/editar_proyecto',$data);
            $this->load->view('commons/footer');
        }

    }

    public function validateUrl()
    {
        $username = $this->session->userdata['logged_in']['username'];
        $data['username'] = $username;
        $url = $this->uri->segment(1);

        $u = new Usuario();
        $usuario = $u->getRolByUsername($username);
        $rol = $usuario[0]->ID_rol;

        $p = new Permisos();
        $permiso = $p->getPermiso($rol, $url);

        if($permiso)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function crearProyecto()
    {
//este funciona
        $this->form_validation->set_rules('nombre', 'inputNombre', 'trim|required', array('required' => 'No ingreso título del proyecto'));
        $this->form_validation->set_rules('descripcion', 'inputDescripcion', 'trim|required',array('required' => 'No ingreso descripción'));

        $p = new Proyecto();

        $p->setNombre($_POST["nombre"]);
        $p->setDescripcion($_POST["descripcion"]);
        $p->setIdRubroProyecto($_POST["comboRubros"]);

        if ($this->form_validation->run() == FALSE)
        {
            $r = new Rubro();
            $data['rubros'] = $r->getRubros();
            $data['username'] = $this->session->userdata['logged_in']['username'];
            $this->load->view('commons/header', $data);
            $this->load->view('emprendedor/crear_proyecto',$data);
            $this->load->view('commons/footer');
        }
        else
        {
            $data['username'] = $this->session->userdata['logged_in']['username'];
            $e = new Emprendedor();
            $id = $e->getIdEmprendedor($data['username']);
            $p->setIdUsuarioEmprendedor($id[0]->ID_usuario);
            $data['userid'] = $id[0]->ID_usuario;

            $date = date('Y-m-d');

            $p->setFechaAlta($date);
            $p->setFechaUltimaModificacion($date);

            $date = strtotime("+30 days", strtotime($date));
            $date = date("Y-m-d", $date);
            $p->setFechaBaja($date);
            if($p->insertProyecto())
            {
                $proyecto = new Proyecto();
                $resultado = $proyecto->getInfoBasicaProyectoByNombre($_POST["nombre"],$id[0]->ID_usuario);

                redirect('video/'.$resultado->ID_proyecto);
            }
        }
    }

    public function subirVideo()
    {
        $this->form_validation->set_rules('video', 'inputVideo', 'trim|required', array('required' => 'No ingresó url del video'));

        $url = new MultimediaProyecto();
        $url->setTipo('youtube');
        $url->setPath($_POST["video"]);
        $url->setIdProyecto($this->uri->segment(3));
        if ($this->form_validation->run() == FALSE)
        {
            $data['username'] = $this->session->userdata['logged_in']['username'];
            $id = $this->uri->segment(3);

            $proyecto = new Proyecto();
            $resultado = $proyecto->getProyectoBasicoById($id);

            if(!$resultado)
            {
                $error = new ErrorPropio();
                $error->Error_bd();
            }
            else
            {
                $data['proyecto'] = $resultado;
                $this->load->view('commons/header',$data);
                $this->load->view('emprendedor/subir_video',$data);
                $this->load->view('commons/footer');
            }
        }
        else
        {
            $path = substr($_POST["video"],32);  // https://www.youtube.com/watch?v=Ibv2ZoLgcyg
            $url->setPath($path);

            if($url->insertMultimedia())
            {
                $id = $this->uri->segment(3);

                redirect('imagenes/'.$id);
            }
        }

    }

    public function guardarImgBD($upload_path)
    {
        $id = $this->uri->segment(3);
        $url = new MultimediaProyecto();
        $cantImg = count($url->imgPorProyecto($id));
        $url->setPath($upload_path);
        $url->setIdProyecto($id);

        if($cantImg==null)
        {
            $url->setTipo('previsualizacion');

            if($url->insertMultimedia())
            {
                $url->setTipo('imagen');
            }
        }
        else
        {
            $url->setTipo('imagen');
        }

        if($url->insertMultimedia())
        {
            redirect('imagenes/'.$id);
        }
    }

    public function guardarPdfBD($upload_path)
    {
        $url = new MultimediaProyecto();
        $url->setTipo('pdf');

        $url->setPath($upload_path);
        $url->setIdProyecto($this->uri->segment(3));

        if($url->insertMultimedia())
        {
            $id = $this->uri->segment(3);
            $msg = 'El_archivo_se_ha_subido_correctamente';

            redirect('archivo/'.$id.'/'.$msg);
        }
        else
        {
            echo 'algo anda mal';
        }
    }

    public function do_upload_img()
    {
        $base_upload_path = '/uploads/';
        $date = strtotime(date('Y-m-d H:i:s'));
        $path = $base_upload_path.$date;

        $filename = basename($path);
        $new = hash("sha256",$filename);
        $bd_upload_path = $new.'.jpg';

        $config = array(
            'upload_path' => './uploads',
            'file_name' => $new.'.jpg',
            'file_type' => "jpg",
            'allowed_types' => "gif|jpg|png|jpeg",
            'overwrite' => FALSE
        );

        $this->load->library('upload', $config);

        if($this->upload->do_upload())
        {
            $this->guardarImgBD($bd_upload_path);
        }
        else
        {
            $this->failImagenProyecto();
        }
    }

    public function no_img_upload()
    {
        if($this->guardarImgBD('image-not-available.jpg'))
        {
            $data['special_case'] = 'si';
        }
        else
        {
            $error = new ErrorPropio();
            $error->Error_bd();
        }
    }

    public function failImagenProyecto ()
    {
        $data['username'] = $this->session->userdata['logged_in']['username'];
        $id = $this->uri->segment(3);

        $proyecto = new Proyecto();
        $resultado = $proyecto->getProyectoBasicoById($id);

        if(!$resultado)
        {
            $error = new ErrorPropio();
            $error->Error_bd();
        }
        else
        {
            $multimedia = new MultimediaProyecto();
            $cantImg = count($multimedia->imgPorProyecto($id));

            $data['error'] = null;
            $data['warning'] = 'La imagen no pudo subirse, verifique que sea formato .jpg.';
            $data['proyecto'] = $resultado;
            $data['cantimg'] = $cantImg;
            $data['special_case'] = null;
            $this->load->view('commons/header', $data);
            $this->load->view('emprendedor/subir_imagen',$data);
            $this->load->view('commons/footer');
        }
    }

    public function do_update_desc()
    {
        $p = new Proyecto();
        $description = $this->input->post('descripcion');
        $id = $this->uri->segment(3);

        if ($p->updateProjectDescription($id, $description)) {
            redirect('emprendedor/editarproyecto/' . $id);
        }
    }

    public function do_update_title()
    {
        $p = new Proyecto();
        $name = $this->input->post('nombre');
        $id = $this->uri->segment(3);

        if ($p->updateProjectName($id, $name)) {
            redirect('emprendedor/editarproyecto/' . $id);
        }
    }

    public function do_update_video()
    {
        $video = $this->input->post('video');
        //echo $video;
        $path = substr($video,32);
        $id = $this->uri->segment(3);

        $p = new Proyecto();

        if($p->updateProjectVideo($id,$path)){
            redirect('emprendedor/editarproyecto/' . $id);
        }
        else
        {
            echo 'no grabo nada';
        }
    }

    public function do_set_video()
    {
        $video = $this->input->post('video');
        //echo $video;
        $path = substr($video,32);
        $id = $this->uri->segment(3);
        $mm = new MultimediaProyecto();
        $mm->setPath($path);
        $mm->setTipo('youtube');
        $mm->setIdProyecto($id);
        
        $p = new Proyecto();

        if($mm->insertMultimedia()){
            redirect('emprendedor/editarproyecto/' . $id);
        }
        else
        {
            echo 'no grabo nada';
        }
    
    }
    



    public function do_update_img($id,$name)
    {
        $id = $this->uri->segment(3);
        $name = $this->uri->segment(4);

        $config = array(
            'upload_path' => './uploads',
            'file_name' => $name,
            'file_type' => "jpg",
            'allowed_types' => "gif|jpg|png|jpeg",
            'overwrite' => FALSE
        );

        $this->load->library('upload');
        $this->upload->initialize($config);
        var_dump($this->upload->do_upload());
        /*
        if()
        {
            redirect('emprendedor/editarproyecto/'.$id);
        }
        else
        {

        }*/
    }

    public function do_update_pdf($id)
    {
        $p = new Proyecto();
        $pdfName = $p->getPDFNameById($id);

        $config = array(
            'upload_path' => './uploads',
            'file_name' => $pdfName[0]->path,
            'file_type' => "pdf",
            'allowed_types' => "pdf",
            'overwrite' => TRUE
        );

        $this->load->library('upload');
        $this->upload->initialize($config);
        if($this->upload->do_upload())
        {
            redirect('emprendedor/editarproyecto/'.$id);
        }
        else
        {

        }

    }


    public function do_upload_pdf()
    {
        $base_upload_path = base_url().'assets/uploads/';
        $date = strtotime(date('Y-m-d H:i:s'));
        $path = $base_upload_path.$date;

        $filename = basename($path);
        $new = hash("sha256",$filename);
        $bd_upload_path = $new.'.pdf';

        $config = array(
            'upload_path' => './uploads',
            'file_name' => $new.'.pdf',
            'file_type' => "pdf",
            'allowed_types' => "pdf",
            'overwrite' => FALSE,
        );

        $this->load->library('upload', $config);

        if($this->upload->do_upload())
        {
            $this->guardarPdfBD($bd_upload_path);
        }
        else
        {
            //borrar
            $this->guardarPdfBD($bd_upload_path);
        }
    }

    public function descripcionProyecto()
    {
        if(!isset($_SESSION['logged_in']))
        {
            $this->load->view('login');
        }
        else
        {
            $data['username'] = $this->session->userdata['logged_in']['username'];

            if($this->validateUrl())
            {
                $id = $this->uri->segment(2);

                if (!$id || !is_numeric($id))
                {
                    $error = new ErrorPropio();
                    $error->Error_bd();
                }

                $CI = &get_instance();
                $CI->config->load("mercadopago", TRUE);
                $config = $CI->config->item('mercadopago');

                $this->load->library('Mercadopago', $config);
                $accessToken = $this->mercadopago->get_access_token();

                $proyecto = new Proyecto();
                $proyecto->getProyectoById($id);
                $resultado = $proyecto->getProyectoById($id);

                if (!$resultado)
                {
                    $error = new ErrorPropio();
                    $error->Error_bd();
                }
                else
                {
                    //sumo una visita! :)
                    $nuevasVisitas = intval($resultado->cant_visitas) + 1;
                    $proyecto->sumarVisitas($id, $nuevasVisitas);

                    $dt1 = $resultado->fecha_baja;
                    $dt1 = date('d', strtotime($dt1));
                    $dt2 = date('d');

                    if($dt1>$dt2)
                    {
                        $diasRestantes = $dt1 - $dt2;
                    }
                    else
                    {
                        $diasRestantes = 30 - ($dt2 - $dt1);
                    }

                    $pdf = $proyecto->getPDFbyIdProyecto($id);
                    $imgs = $proyecto->getImgsByIdProyecto($id);

                    $preference_data = array(
                        "items" => array(
                            array(
                                "title" => $resultado->nombre,
                                "id_proyecto" => $resultado->ID_proyecto,
                                "currency_id" => "ARS",
                                "quantity" => 1,
                                "unit_price" => 1
                            )
                        )
                    );

                    $preference = $this->mercadopago->create_preference($preference_data);

                    $data['proyecto'] = $resultado;
                    $data['dias_restantes'] = $diasRestantes;
                    $data['pdf'] = $pdf;
                    $data['cant_img'] = count($imgs);
                    $data['imgs'] = $imgs;
                    $data['mp_preference'] = $preference;
                    $data['token'] = $accessToken;
                    $this->load->view('commons/header', $data);
                    $this->load->view('proyecto', $data);
                    $this->load->view('commons/footer');
                }
            }
            else
            {
                echo 'sin permisos';
            }

        }

    }

    public function descripcionProyectoEmprendedor()
    {
        $data['username'] = $this->session->userdata['logged_in']['username'];
        $id = $this->uri->segment(2);

        if (!$id || !is_numeric($id))
        {
            $error = new ErrorPropio();
            $error->Error_bd();
        }

        $proyecto = new Proyecto();
        $proyecto->getProyectoById($id);
        $resultado = $proyecto->getProyectoById($id);

        if (!$resultado)
        {
            $error = new ErrorPropio();
            $error->Error_bd();
        }
        else
        {
            $pdf = $proyecto->getPDFbyIdProyecto($id);
            $imgs = $proyecto->getImgsByIdProyecto($id);
            $dt1 = $resultado->fecha_alta;
            $dt2 = $resultado->fecha_baja;

            $data['proyecto'] = $resultado;
            $data['pdf'] = $pdf;
            $data['cant_img'] = count($imgs);
            $data['imgs'] = $imgs;
            $data['fecha_alta'] = $dt1;
            $data['fecha_baja'] = $dt2;
            $this->load->view('commons/header', $data);
            $this->load->view('emprendedor/vip_emprendedor', $data);
            $this->load->view('commons/footer');
        }
    }
}