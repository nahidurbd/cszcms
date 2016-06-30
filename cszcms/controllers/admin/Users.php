<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Users extends CI_Controller {

    function __construct() {
        parent::__construct();
        define('LANG', $this->Csz_admin_model->getLang());
        $this->lang->load('admin', LANG);
        $this->template->set_template('admin');
        $this->_init();
    }

    public function _init() {
        $row = $this->Csz_admin_model->load_config();
        $pageURL = $this->Csz_admin_model->getCurPages();
        $this->template->set('core_css', $this->Csz_admin_model->coreCss());
        $this->template->set('core_js', $this->Csz_admin_model->coreJs());
        $this->template->set('title', 'Backend System | ' . $row->site_name);
        $this->template->set('meta_tags', $this->Csz_admin_model->coreMetatags('Backend System for CSZ Content Management'));
        $this->template->set('cur_page', $pageURL);
    }

    public function index() {
        admin_helper::is_logged_in($this->session->userdata('admin_email'));
        $this->load->library('pagination');
        $this->csz_referrer->setIndex();
        $search_arr = '';
        if($this->input->get('search') || $this->input->get('user_type')){
            $search_arr.= ' 1=1 ';
            if($this->input->get('search')){
                $search_arr.= " AND name LIKE '%".$this->input->get('search', TRUE)."%' OR email LIKE '%".$this->input->get('search', TRUE)."%'";
            }
            if($this->input->get('user_type')){
                $search_arr.= " AND user_type = '".$this->input->get('user_type', TRUE)."'";
            }
        }
        // Pages variable
        $result_per_page = 20;
        $total_row = $this->Csz_model->countData('user_admin', $search_arr);
        $num_link = 10;
        $base_url = BASE_URL . '/admin/users/';
        // Pageination config
        $this->Csz_admin_model->pageSetting($base_url,$total_row,$result_per_page,$num_link);     
        ($this->uri->segment(3))? $pagination = $this->uri->segment(3) : $pagination = 0;
        //Get users from database
        $this->template->setSub('users', $this->Csz_admin_model->getIndexData('user_admin', $result_per_page, $pagination, 'user_type', 'asc', $search_arr));
        $this->template->setSub('total_row',$total_row);       
        //Load the view
        $this->template->loadSub('admin/users_index');
    }

    public function addUser() {
        admin_helper::is_logged_in($this->session->userdata('admin_email'));
        admin_helper::is_not_admin($this->session->userdata('admin_type'));
        //Load the form helper
        $this->load->helper('form');
        //Load the view
        $this->template->loadSub('admin/users_add');
    }

    public function confirm() {
        admin_helper::is_logged_in($this->session->userdata('admin_email'));
        admin_helper::is_not_admin($this->session->userdata('admin_type'));
        //Load the form validation library
        $this->load->library('form_validation');
        //Set validation rules
        $this->form_validation->set_rules('email', $this->lang->line('user_new_email'), 'trim|required|valid_email|is_unique[user_admin.email]');
        $this->form_validation->set_rules('password', $this->lang->line('user_new_pass'), 'trim|required|min_length[4]|max_length[32]');
        $this->form_validation->set_rules('con_password', $this->lang->line('user_new_confirm'), 'trim|required|matches[password]');
        $this->form_validation->set_message('is_unique', $this->lang->line('is_unique'));
        $this->form_validation->set_message('valid_email', $this->lang->line('valid_email'));
        $this->form_validation->set_message('matches', $this->lang->line('matches'));
        $this->form_validation->set_message('required', $this->lang->line('required'));
        $this->form_validation->set_message('min_length', $this->lang->line('min_length'));
        $this->form_validation->set_message('max_length', $this->lang->line('max_length'));

        if ($this->form_validation->run() == FALSE) {
            //Validation failed
            $this->addUser();
        } else {
            //Validation passed
            //Add the user
            $this->Csz_admin_model->createUser();
            //Return to user list
            redirect($this->csz_referrer->getIndex(), 'refresh');
        }
    }

    public function editUser() {
        admin_helper::is_logged_in($this->session->userdata('admin_email'));
        if($this->session->userdata('admin_type') != 'admin' && $this->session->userdata('user_admin_id') != $this->uri->segment(4)){
            redirect('/admin/users', 'refresh');
        }
        //Load the form helper
        $this->load->helper('form');
        if($this->uri->segment(4)){
            //Get user details from database
            $this->template->setSub('users', $this->Csz_admin_model->getUser($this->uri->segment(4)));
            //Load the view
            $this->template->loadSub('admin/users_edit');
        }else{
            redirect($this->csz_referrer->getIndex(), 'refresh');
        }
    }

    public function edited() {
        admin_helper::is_logged_in($this->session->userdata('admin_email'));
        if($this->session->userdata('admin_type') != 'admin' && $this->session->userdata('user_admin_id') != $this->uri->segment(4)){
            redirect('/admin/users', 'refresh');
        }       
        //Load the form validation library
        $this->load->library('form_validation');
        //Set validation rules
        $this->form_validation->set_rules('email', $this->lang->line('user_new_email'), 'trim|required|valid_email|is_unique[user_admin.email.user_admin_id.' . $this->uri->segment(4) . ']');
        $this->form_validation->set_rules('password', $this->lang->line('user_new_pass'), 'trim|min_length[4]|max_length[32]');
        $this->form_validation->set_rules('con_password', $this->lang->line('user_new_confirm'), 'trim|matches[password]');
        $this->form_validation->set_message('is_unique', $this->lang->line('is_unique'));
        $this->form_validation->set_message('valid_email', $this->lang->line('valid_email'));
        $this->form_validation->set_message('matches', $this->lang->line('matches'));
        $this->form_validation->set_message('required', $this->lang->line('required'));
        $this->form_validation->set_message('min_length', $this->lang->line('min_length'));
        $this->form_validation->set_message('max_length', $this->lang->line('max_length'));

        if ($this->form_validation->run() == FALSE) {
            //Validation failed
            $this->editUser();
        } else {
            //Validation passed
            //Update the user
            $this->Csz_admin_model->updateUser($this->uri->segment(4));
            //Return to user list
            redirect($this->csz_referrer->getIndex(), 'refresh');
        }
    }
    
    public function viewUsers() {
        admin_helper::is_logged_in($this->session->userdata('admin_email'));
        if($this->uri->segment(4)){             
            //Get users from database   
            $this->template->setSub('users', $this->Csz_admin_model->getUser($this->uri->segment(4)));
            //Load the view
            $this->template->loadSub('admin/users_view');
        }else{
            redirect($this->csz_referrer->getIndex(), 'refresh');
        }
    }

    public function delete() {
        admin_helper::is_logged_in($this->session->userdata('admin_email'));
        admin_helper::is_not_admin($this->session->userdata('admin_type'));
        if($this->uri->segment(4)){
            if ($this->session->userdata('user_admin_id') != $this->uri->segment(4)) {
                //Delete the user account
                $this->Csz_admin_model->removeUser($this->uri->segment(4));
            } else {
                echo "<script>alert(\"" . $this->lang->line('user_delete_myacc') . "\");</script>";
            }
        }
        //Return to user list
        redirect($this->csz_referrer->getIndex(), 'refresh');
    }

    /*     * ************ Forgotten Password Resets ************* */

    public function forgot() {
        admin_helper::login_already($this->session->userdata('admin_email'));
        $row = $this->Csz_model->load_config();
        $this->load->library('form_validation');
        $this->form_validation->set_rules('email', $this->lang->line('forgot_email'), 'trim|required|valid_email|callback_email_check');
        $this->form_validation->set_message('valid_email', $this->lang->line('valid_email'));
        $this->form_validation->set_message('required', $this->lang->line('required'));
        if ($this->form_validation->run() == FALSE) {
            $this->template->setSub('chksts', 0);
            $this->template->setSub('error_chk', 0);
            $this->template->loadSub('admin/email_forgot');
        }else if($this->Csz_model->chkCaptchaRes() == ''){
            $this->template->setSub('chksts', 0);
            $this->template->setSub('error_chk', 1);
            $this->template->loadSub('admin/email_forgot');
        } else {
            $email = $this->input->post('email');
            $this->db->set('md5_hash', md5(time()+mt_rand(1, 99999999)), TRUE);
            $this->db->set('md5_lasttime', 'NOW()', FALSE);
            $this->db->where('email', $email);
            $this->db->where("user_type != 'member'");
            $this->db->update('user_admin');
            $this->load->helper('string');
            $user_rs = $this->Csz_model->getValue('md5_hash', 'user_admin', 'email', $email, 1);
            $md5_hash = $user_rs->md5_hash;

            //now we will send an email
            # ---- set subject --#
            $subject = $this->lang->line('email_reset_subject');
            # ---- set from, to, bcc --#
            $from_name = $row->site_name;
            $from_email = 'no-reply@'.EMAIL_DOMAIN;
            $to_email = $email;
            # ---- set header --#
            $headers = 'MIME-Version: 1.0' . "\r\n";
            $headers.= 'Content-type: text/html; charset=utf-8' . "\r\n";
            $headers.= 'From: ' . $from_name . ' <' . $from_email . '>' . "\r\n";           
            $message_html = $this->lang->line('email_dear').$email.',<br><br>'.$this->lang->line('email_reset_message').'<br><a href="'.BASE_URL.'/admin/reset/'.$md5_hash.'" target="_blank"><b>'.BASE_URL.'/admin/reset/'.$md5_hash.'</b></a><br><br>'.$this->lang->line('email_footer').'<a href="'.BASE_URL.'" target="_blank"><b>'.$row->site_name.'</b></a>';
            # ---- send mail --#
            @mail($to_email, $subject, $message_html, $headers);

            $this->template->setSub('error_chk', 0);
            $this->template->setSub('chksts', 1);
            $this->template->loadSub('admin/email_forgot');
        }
    }

    public function email_check($str) {
        admin_helper::login_already($this->session->userdata('admin_email'));
        $this->db->where('email', $str);
        $this->db->where("user_type != 'member'");
        $this->db->limit(1, 0);
        $query = $this->db->get('user_admin');
        if ($query->num_rows() == 1) {
            return true;
        } else {
            $this->form_validation->set_message('email_check', $this->lang->line('email_check'));
            return false;
        }
    }

    public function getPassword() {
        admin_helper::login_already($this->session->userdata('admin_email'));
        $md5_hash = $this->uri->segment(3);
        $this->Csz_admin_model->chkMd5Time($md5_hash);
        $user_rs = $this->Csz_model->getValue('*', 'user_admin', 'md5_hash', $md5_hash, 1);
        if (!$user_rs){
            redirect('admin/user/forgot', 'refresh');
        } else {
            $this->template->setSub('email', $user_rs->email);
            $this->load->helper('form');
            $this->load->library('form_validation');
            $this->form_validation->set_rules('password', $this->lang->line('user_new_pass'), 'trim|required|min_length[4]|max_length[32]|matches[con_password]');
            $this->form_validation->set_rules('con_password', $this->lang->line('user_new_confirm'), 'trim|required');
            $this->form_validation->set_message('matches', $this->lang->line('matches'));
            $this->form_validation->set_message('required', $this->lang->line('required'));
            $this->form_validation->set_message('min_length', $this->lang->line('min_length'));
            $this->form_validation->set_message('max_length', $this->lang->line('max_length'));
            if ($this->form_validation->run() == FALSE) {
                $this->template->setSub('success_chk', 0);
                $this->template->loadSub('admin/resetform');
            } else {
                if (!$user_rs->email) {
                    show_error('Sorry!!! Invalid Request!');
                } else {
                    $data = array(
                        'password' => md5($this->input->post('password')),
                        'md5_hash' => md5(time()+mt_rand(1, 99999999)),
                    );
                    $this->db->set('md5_lasttime', 'NOW()', FALSE);
                    $this->db->where('md5_hash', $md5_hash);
                    $this->db->update('user_admin', $data);
                    
                    $this->template->setSub('success_chk', 1);
                    $this->template->loadSub('admin/resetform');
                }
            }
        }
    }

}
