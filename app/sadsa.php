<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller {

    function __construct()
    {

        parent::__construct();
        $this->load->database();
        $this->load->library(array('ion_auth','form_validation'));
        $this->load->helper(array('url','language'));

        $this->form_validation->set_error_delimiters($this->config->item('error_start_delimiter', 'ion_auth'), $this->config->item('error_end_delimiter', 'ion_auth'));

        $this->lang->load('auth');

        $this->data['is_admin']  = $this->ion_auth->is_admin();
        $this->data['logged_in'] = $this->ion_auth->logged_in();

        // Not logged in? redirect to login
        if ( ! $this->ion_auth->logged_in() && ! in_array($this->router->method, ['register','login','forgot_password','reset_password','activate']))
        {

            $this->session->set_flashdata('message', 'لطفا مجددا وارد شوید');

            redirect('auth/login', 'refresh');
        }
        // Logged in but not verified? only allow 2 pages
        elseif ($this->ion_auth->logged_in() && ! $this->ion_auth->is_admin() && intval($this->ion_auth->user()->row()->allow_exchange) !== 1 && ! in_array($this->router->method, ['register','login','forgot_password','reset_password','activate','account','verify','logout','need_verify']))
        {
            redirect('auth/need_verify', 'refresh');
        }

        // Check IP Blacklist
        $this->_ip_blacklist();

        // Important News
        $this->data['important_news'] = $this->db->query("SELECT important_news FROM `app_settings` WHERE id = 1")->row()->important_news;
    }

    // redirect if needed, otherwise display the user list
    function index()
    {

        // Not logged in? redirect to login
        if (!$this->ion_auth->logged_in())
        {
            redirect('auth/login', 'refresh');
        }

        // Logged in and admin? redirect to admin
        elseif ($this->ion_auth->logged_in() && $this->ion_auth->is_admin())
        {
            $this->manager();
        }

        // Redirect to user account
        else
        {
            redirect('auth/account', 'refresh');
        }
    }


    // redirect if needed, otherwise display the user list
    function manager()
    {

        if (!$this->ion_auth->logged_in())
        {
            // redirect them to the login page
            redirect('auth/login', 'refresh');
        }
        elseif (!$this->ion_auth->is_admin() OR intval($this->ion_auth->get_user_id()) !== 1)
        {
            // redirect them to the logout because they must be an administrator to view this
            return show_error('صفحه مورد نظر یافت نشد');
        }
        else
        {
            if($this->_require_second_password() !== TRUE)
            {
                return FALSE;
            }

            // set the flash data error message if there is one
            $this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');

            //list the users
            $count_users               = $this->db->query("SELECT IFNULL(count(id),0) AS counted FROM `app_users`")->row();
            $this->data['count_users'] = $count_users->counted;

            // Show all or only 50
            if ($this->uri->segment(3) === 'show_all')
            {
                $this->data['show_all'] = TRUE;
                $this->data['users'] = $this->db->get('app_users')->result();
            }
            else
            {
                $this->data['show_all'] = FALSE;
                $this->data['users'] = $this->db->order_by('id', 'DESC')->get('app_users', 50, 0)->result();
            }

            foreach ($this->data['users'] as $k => $user)
            {
                $this->data['users'][$k]->groups = $this->ion_auth->get_users_groups($user->id)->result();
            }

            // List Comments
            $this->db->select('*');
            $this->db->where('allow', 0);
            $comments = $this->db->get('app_comments')->result();
            $this->data['comments'] = $comments;

            $w = 0;
            foreach($comments as $c){$w++;}
            if ($w > 0){$this->data['show_comments']=TRUE;}else{$this->data['show_comments']=FALSE;}

            $this->_render_page('auth/manager', $this->data);
        }
    }

    function comment_allow()
    {
        if ( ! $this->ion_auth->is_admin())
        {
            return show_error('صفحه مورد نظر یافت نشد');
        }
        else
        {
            if($this->_require_second_password() !== TRUE)
            {
                return FALSE;
            }

            $comment_id = intval($this->uri->segment(3));

            $this->db->set('allow', 1);
            $this->db->where('id', $comment_id);
            $this->db->update('app_comments');

            $this->session->set_flashdata('message', 'پیغام تایید شد');
        }
        redirect('auth/manager', 'refresh');
    }


    function comment_delete()
    {
        if ( ! $this->ion_auth->is_admin() OR intval($this->ion_auth->get_user_id()) !== 1)
        {
            return show_error('صفحه مورد نظر یافت نشد');
            die();
        }
        else
        {
            $comment_id = intval($this->uri->segment(3));

            $this->db->where('id', $comment_id);
            $this->db->delete('app_comments');

            $this->session->set_flashdata('message', 'پاک شد');
        }
        redirect('auth/manager', 'refresh');
    }


    // delete image
    function delete_img($id)
    {
        $user_id = intval($id);
        if ( ! $this->ion_auth->is_admin() OR intval($this->ion_auth->get_user_id()) !== 1 OR $user_id < 1)
        {
            return show_error('صفحه مورد نظر یافت نشد');
            die();
        }
        else
        {
            $ext     = htmlspecialchars(trim($this->uri->segment(4)));
            $file_path = FCPATH."program/verify_scans/document_userid_{$user_id}.{$ext}";

            if (file_exists($file_path)) {
                unlink($file_path);
                $this->session->set_flashdata('message', 'تصویر از هاست پاک شد');
            } else {
                $this->session->set_flashdata('message', 'به نظر این کاربر تصویری آپلود نکرده');
            }
        }
        redirect("auth/edit_user/{$user_id}", 'refresh');
    }


    // redirect if needed, otherwise display the search user
    function search_user()
    {
        if ( ! $this->ion_auth->is_admin() OR intval($this->ion_auth->get_user_id()) !== 1)
        {
            return show_error('صفحه مورد نظر یافت نشد');
            die();
        }

        if($this->_require_second_password() !== TRUE)
        {
            return FALSE;
        }

        $this->data['users'] = array();
        $this->data['found_search'] = 0;
        $this->data['search_display'] = '';

        // set the flash data error message if there is one
        $this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');

        $search_user_method = $this->input->post('search_user_method');
        $search_user_value  = $this->input->post('search_user_value');

        if ($search_user_method &&  in_array($search_user_method, ['first_name','last_name','email','mobile','phone','melli','ip_address']))
        {
            $method = $this->security->xss_clean($search_user_method);
        }

        if ($search_user_value &&  strlen($search_user_value) > 0)
        {
            $value = trim(htmlspecialchars($this->security->xss_clean($search_user_value)));
        }

        //list searched users
        if (isset($method) && isset($value))
        {
            $this->data['search_display'] = "{$method} = {$search_user_value}";
            $this->db->like("$method", "$value");
            $this->data['users'] = $this->db->get('app_users')->result();

            foreach ($this->data['users'] as $k => $user)
            {
                $this->data['found_search']++;
            }
        }

        $this->_render_page('auth/search_user', $this->data);
    }

    // signup
    function register()
    {
        $this->data['title'] = "Registration";

        // Only Iran
        if(isset($_SERVER["HTTP_CF_IPCOUNTRY"]) && strlen($_SERVER["HTTP_CF_IPCOUNTRY"]) > 0 && $_SERVER["HTTP_CF_IPCOUNTRY"] != 'IR' && $this->config->item('signup_only_form_iran') === TRUE)
        {
            die('عضویت فقط با آی پی ایران ممکن است، در صورتی که فکر می کنید آی پی شما ایران است با مدیریت تماس بگیرید تا برای شما اکانت ایجاد شود');
        }

        if ($this->ion_auth->logged_in())
        {
            redirect('auth', 'refresh');
        }

        $tables = $this->config->item('tables','ion_auth');
        $identity_column = $this->config->item('identity','ion_auth');
        $this->data['identity_column'] = $identity_column;

        // validate form input
        $this->form_validation->set_rules('first_name', $this->lang->line('create_user_validation_fname_label'), 'required');
        $this->form_validation->set_rules('last_name', $this->lang->line('create_user_validation_lname_label'), 'required');
        $this->form_validation->set_rules('melli', $this->lang->line('create_user_validation_melli_label'), 'required');
        $this->form_validation->set_rules('address', $this->lang->line('create_user_validation_address_label'), 'required');
        if($identity_column!=='email')
        {
            $this->form_validation->set_rules('identity',$this->lang->line('create_user_validation_identity_label'),'required|is_unique['.$tables['users'].'.'.$identity_column.']');
            $this->form_validation->set_rules('email', $this->lang->line('create_user_validation_email_label'), 'required|valid_email');
        }
        else
        {
            $this->form_validation->set_rules('email', $this->lang->line('create_user_validation_email_label'), 'required|valid_email|is_unique[' . $tables['users'] . '.email]');
        }
        $this->form_validation->set_rules('phone', $this->lang->line('create_user_validation_phone_label'), 'trim');
        $this->form_validation->set_rules('mobile', $this->lang->line('create_user_validation_mobile_label'), 'trim');
        $this->form_validation->set_rules('password', $this->lang->line('create_user_validation_password_label'), 'required|min_length[' . $this->config->item('min_password_length', 'ion_auth') . ']|max_length[' . $this->config->item('max_password_length', 'ion_auth') . ']|matches[password_confirm]');
        $this->form_validation->set_rules('password_confirm', $this->lang->line('create_user_validation_password_confirm_label'), 'required');

        if ($this->form_validation->run() == true)
        {
            $email    = strtolower($this->input->post('email'));
            $identity = ($identity_column==='email') ? $email : $this->input->post('identity');
            $password = $this->input->post('password');

            $additional_data = array(
                'first_name' => html_escape($this->input->post('first_name', TRUE)),
                'last_name'  => html_escape($this->input->post('last_name', TRUE)),
                'melli'      => html_escape($this->input->post('melli', TRUE)),
                'address'    => html_escape($this->input->post('address', TRUE)),
                'mobile'     => html_escape($this->input->post('mobile', TRUE)),
                'phone'      => html_escape($this->input->post('phone', TRUE)),
            );
            $webpurse_email = $this->config->item('webpurse_email');
            mail("$webpurse_email", 'Ozv Jadid', 'سایت یک عضو جدید دارد', 'From: info@webpurse.org');
            mail($email, 'WebPurse.org', 'ورود شما را به وب سایت WebPurse.org خوش آمد میگوییم', 'From: info@webpurse.org');
        }
        if ($this->form_validation->run() == true && $this->ion_auth->register($identity, $password, $email, $additional_data))
        {
            // check to see if we are creating the user
            // redirect them back to the admin page
            $this->session->set_flashdata('message', $this->ion_auth->messages());
            redirect("auth", 'refresh');
        }
        else
        {
            // display the create user form
            // set the flash data error message if there is one
            $this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));

            $this->data['first_name'] = array(
                'name'  => 'first_name',
                'id'    => 'first_name',
                'type'  => 'text',
                'value' => html_escape($this->form_validation->set_value('first_name')),
            );
            $this->data['last_name'] = array(
                'name'  => 'last_name',
                'id'    => 'last_name',
                'type'  => 'text',
                'value' => html_escape($this->form_validation->set_value('last_name')),
            );
            $this->data['melli'] = array(
                'name'  => 'melli',
                'id'    => 'melli',
                'type'  => 'text',
                'value' => html_escape($this->form_validation->set_value('melli')),
            );
            $this->data['address'] = array(
                'name'  => 'address',
                'id'    => 'address',
                'type'  => 'text',
                'value' => html_escape($this->form_validation->set_value('address')),
            );
            $this->data['identity'] = array(
                'name'  => 'identity',
                'id'    => 'identity',
                'type'  => 'text',
                'value' => html_escape($this->form_validation->set_value('identity')),
            );
            $this->data['email'] = array(
                'name'  => 'email',
                'id'    => 'email',
                'type'  => 'text',
                'value' => html_escape($this->form_validation->set_value('email')),
            );
            $this->data['mobile'] = array(
                'name'  => 'mobile',
                'id'    => 'mobile',
                'type'  => 'text',
                'value' => html_escape($this->form_validation->set_value('mobile')),
            );
            $this->data['phone'] = array(
                'name'  => 'phone',
                'id'    => 'phone',
                'type'  => 'text',
                'value' => html_escape($this->form_validation->set_value('phone')),
            );
            $this->data['password'] = array(
                'name'  => 'password',
                'id'    => 'password',
                'type'  => 'password',
                'value' => $this->form_validation->set_value('password'),
            );
            $this->data['password_confirm'] = array(
                'name'  => 'password_confirm',
                'id'    => 'password_confirm',
                'type'  => 'password',
                'value' => $this->form_validation->set_value('password_confirm'),
            );

            $this->_render_page('auth/register', $this->data);
        }
    }


    // log the user in
    function login()
    {

        // Logged in?
        if ($this->ion_auth->logged_in())
        {
            // Admin
            if ($this->ion_auth->is_admin())
            {
                redirect('auth/manager', 'refresh');
            }

            // User
            else
            {
                redirect('auth/account', 'refresh');
            }

        }

        $this->data['title'] = "Login";

        //validate form input
        $this->form_validation->set_rules('identity', 'Identity', 'required');
        $this->form_validation->set_rules('password', 'Password', 'required');

        if ($this->form_validation->run() == true)
        {
            // check to see if the user is logging in
            // check for "remember me"
            $remember = (bool) $this->input->post('remember');

            if ($this->ion_auth->login($this->input->post('identity'), $this->input->post('password'), $remember))
            {
                //if the login is successful
                //redirect them to account page
                $this->session->set_flashdata('message', $this->ion_auth->messages());
                if ($this->ion_auth->is_admin())
                {
                    redirect('auth/manager', 'refresh');
                }

                redirect('/auth/account', 'refresh');
            }
            else
            {
                // if the login was un-successful
                // redirect them back to the login page
                $this->session->set_flashdata('message', $this->ion_auth->errors());
                redirect('auth/login', 'refresh'); // use redirects instead of loading views for compatibility with MY_Controller libraries
            }
        }
        else
        {
            // the user is not logging in so display the login page
            // set the flash data error message if there is one
            $this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');

            $this->data['identity'] = array('name' => 'identity',
                'id'    => 'identity',
                'type'  => 'text',
                'value' => $this->form_validation->set_value('identity'),
            );
            $this->data['password'] = array('name' => 'password',
                'id'   => 'password',
                'type' => 'password',
            );

            $this->_render_page('auth/login', $this->data);
        }
    }


    // log the user out
    function logout()
    {
        $this->data['title'] = "Logout";
        // log the user out
        $logout = $this->ion_auth->logout();

        // redirect them to the login page
        $this->session->set_flashdata('message', $this->ion_auth->messages());
        redirect('auth/login', 'refresh');
    }


    // change password
    function change_password()
    {
        $this->form_validation->set_rules('old', $this->lang->line('change_password_validation_old_password_label'), 'required');
        $this->form_validation->set_rules('new', $this->lang->line('change_password_validation_new_password_label'), 'required|min_length[' . $this->config->item('min_password_length', 'ion_auth') . ']|max_length[' . $this->config->item('max_password_length', 'ion_auth') . ']|matches[new_confirm]');
        $this->form_validation->set_rules('new_confirm', $this->lang->line('change_password_validation_new_password_confirm_label'), 'required');

        if ( ! $this->ion_auth->logged_in())
        {
            redirect('auth/login', 'refresh');
        }

        $user = $this->ion_auth->user()->row();

        if ($this->form_validation->run() == false)
        {
            // display the form
            // set the flash data error message if there is one
            $this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');

            $this->data['min_password_length'] = $this->config->item('min_password_length', 'ion_auth');
            $this->data['old_password'] = array(
                'name' => 'old',
                'id'   => 'old',
                'type' => 'password',
            );
            $this->data['new_password'] = array(
                'name'    => 'new',
                'id'      => 'new',
                'type'    => 'password',
                'pattern' => '^.{'.$this->data['min_password_length'].'}.*$',
            );
            $this->data['new_password_confirm'] = array(
                'name'    => 'new_confirm',
                'id'      => 'new_confirm',
                'type'    => 'password',
                'pattern' => '^.{'.$this->data['min_password_length'].'}.*$',
            );
            $this->data['user_id'] = array(
                'name'  => 'user_id',
                'id'    => 'user_id',
                'type'  => 'hidden',
                'value' => $user->id,
            );

            $this->data['csrf'] = $this->_get_csrf_nonce();

            // render
            $this->_render_page('auth/change_password', $this->data);
        }
        else
        {
            if ($this->_valid_csrf_nonce() === FALSE)
            {
                show_error($this->lang->line('error_csrf'));
                die();
            }

            $identity = $this->session->userdata('identity');

            $change = $this->ion_auth->change_password($identity, $this->input->post('old'), $this->input->post('new'));

            if ($change)
            {
                //if the password was successfully changed
                $this->session->set_flashdata('message', $this->ion_auth->messages());
                $this->logout();
            }
            else
            {
                $this->session->set_flashdata('message', $this->ion_auth->errors());
                redirect('auth/change_password', 'refresh');
            }
        }
    }


    // change password
    function change_second_password()
    {
        if (!$this->ion_auth->logged_in() OR !$this->ion_auth->is_admin() OR intval($this->ion_auth->get_user_id()) !== 1)
        {
            return show_error('صفحه مورد نظر یافت نشد');
            die();
        }

        if($this->_require_second_password() !== TRUE)
        {
            return FALSE;
        }

        $this->form_validation->set_rules('old', $this->lang->line('change_password_validation_old_password_label'), 'required');
        $this->form_validation->set_rules('new', $this->lang->line('change_password_validation_new_password_label'), 'required|min_length[' . $this->config->item('min_password_length', 'ion_auth') . ']|max_length[' . $this->config->item('max_password_length', 'ion_auth') . ']|matches[new_confirm]');
        $this->form_validation->set_rules('new_confirm', $this->lang->line('change_password_validation_new_password_confirm_label'), 'required');


        if ($this->form_validation->run() == false)
        {
            // display the form
            // set the flash data error message if there is one
            $this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');

            $this->data['min_password_length'] = $this->config->item('min_password_length', 'ion_auth');
            $this->data['old_password'] = array(
                'name' => 'old',
                'id'   => 'old',
                'type' => 'password',
            );
            $this->data['new_password'] = array(
                'name'    => 'new',
                'id'      => 'new',
                'type'    => 'password',
                'pattern' => '^.{'.$this->data['min_password_length'].'}.*$',
            );
            $this->data['new_password_confirm'] = array(
                'name'    => 'new_confirm',
                'id'      => 'new_confirm',
                'type'    => 'password',
                'pattern' => '^.{'.$this->data['min_password_length'].'}.*$',
            );

            // render
            $this->_render_page('auth/change_second_password', $this->data);
        }
        else
        {
            // Get old Password
            $pass_salt        = $this->db->query('SELECT salt,second_password FROM app_settings WHERE id=1')->row();
            $db_password      = $pass_salt->second_password;
            $db_salt          = $pass_salt->salt;

            $posted_old_pass  = $this->input->post('old');
            $old_pass_hash    = hash('sha256', "{$db_salt}{$posted_old_pass}");

            $posted_new_pass  = $this->input->post('new');
            $new_pass_hash    = hash('sha256', "{$db_salt}{$posted_new_pass}");

            // Check posted password
            if (strcmp($old_pass_hash, $db_password) === 0)
            {
                $data = array(
                    'second_password' => $new_pass_hash
                );
                $this->db->where('id', 1);
                $change = $this->db->update('app_settings', $data);

                if ($change)
                {
                    //if the password was successfully changed
                    $this->session->set_flashdata('message', 'رمز دوم با موفقیت تغییر کرد');
                }
                else
                {
                    $this->session->set_flashdata('message', 'خطایی رخ داد لطفا مجددا تلاش کنید');
                }

                $this->logout();
            }
            else
            {
                $this->session->set_flashdata('message', 'رمز دوم قبلی اشتباه است');
                redirect('auth/change_second_password', 'refresh');
            }
        }
    }


    // forgot password
    function forgot_password()
    {
        // setting validation rules by checking wheather identity is username or email
        if($this->config->item('identity', 'ion_auth') != 'email' )
        {
            $this->form_validation->set_rules('identity', $this->lang->line('forgot_password_identity_label'), 'required');
        }
        else
        {
            $this->form_validation->set_rules('identity', $this->lang->line('forgot_password_validation_email_label'), 'required|valid_email');
        }


        if ($this->form_validation->run() == false)
        {
            $this->data['type'] = $this->config->item('identity','ion_auth');
            // setup the input
            $this->data['identity'] = array('name' => 'identity',
                'id' => 'identity',
            );

            if ( $this->config->item('identity', 'ion_auth') != 'email' ){
                $this->data['identity_label'] = $this->lang->line('forgot_password_identity_label');
            }
            else
            {
                $this->data['identity_label'] = $this->lang->line('forgot_password_email_identity_label');
            }

            // set any errors and display the form
            $this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');
            $this->_render_page('auth/forgot_password', $this->data);
        }
        else
        {
            $identity_column = $this->config->item('identity','ion_auth');
            $identity = $this->ion_auth->where($identity_column, $this->input->post('identity'))->users()->row();

            if(empty($identity)) {

                if($this->config->item('identity', 'ion_auth') != 'email')
                {
                    $this->ion_auth->set_error('forgot_password_identity_not_found');
                }
                else
                {
                    $this->ion_auth->set_error('forgot_password_email_not_found');
                }

                $this->session->set_flashdata('message', $this->ion_auth->errors());
                redirect("auth/forgot_password", 'refresh');
            }

            // run the forgotten password method to email an activation code to the user
            $forgotten = $this->ion_auth->forgotten_password($identity->{$this->config->item('identity', 'ion_auth')});

            if ($forgotten)
            {
                // if there were no errors
                $this->session->set_flashdata('message', $this->ion_auth->messages());
                redirect("auth/login", 'refresh'); //we should display a confirmation page here instead of the login page
            }
            else
            {
                $this->session->set_flashdata('message', $this->ion_auth->errors());
                redirect("auth/forgot_password", 'refresh');
            }
        }
    }

    // reset password - final step for forgotten password
    public function reset_password($code = NULL)
    {
        if (!$code)
        {
            show_404();
        }

        $user = $this->ion_auth->forgotten_password_check($code);

        if ($user)
        {
            // if the code is valid then display the password reset form

            $this->form_validation->set_rules('new', $this->lang->line('reset_password_validation_new_password_label'), 'required|min_length[' . $this->config->item('min_password_length', 'ion_auth') . ']|max_length[' . $this->config->item('max_password_length', 'ion_auth') . ']|matches[new_confirm]');
            $this->form_validation->set_rules('new_confirm', $this->lang->line('reset_password_validation_new_password_confirm_label'), 'required');

            if ($this->form_validation->run() == false)
            {
                // display the form

                // set the flash data error message if there is one
                $this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');

                $this->data['min_password_length'] = $this->config->item('min_password_length', 'ion_auth');
                $this->data['new_password'] = array(
                    'name' => 'new',
                    'id'   => 'new',
                    'type' => 'password',
                    'pattern' => '^.{'.$this->data['min_password_length'].'}.*$',
                );
                $this->data['new_password_confirm'] = array(
                    'name'    => 'new_confirm',
                    'id'      => 'new_confirm',
                    'type'    => 'password',
                    'pattern' => '^.{'.$this->data['min_password_length'].'}.*$',
                );
                $this->data['user_id'] = array(
                    'name'  => 'user_id',
                    'id'    => 'user_id',
                    'type'  => 'hidden',
                    'value' => $user->id,
                );
                $this->data['csrf'] = $this->_get_csrf_nonce();
                $this->data['code'] = $code;

                // render
                $this->_render_page('auth/reset_password', $this->data);
            }
            else
            {
                // do we have a valid request?
                if ($this->_valid_csrf_nonce() === FALSE || $user->id != $this->input->post('user_id'))
                {

                    // something fishy might be up
                    $this->ion_auth->clear_forgotten_password_code($code);

                    show_error($this->lang->line('error_csrf'));

                }
                else
                {
                    // finally change the password
                    $identity = $user->{$this->config->item('identity', 'ion_auth')};

                    $change = $this->ion_auth->reset_password($identity, $this->input->post('new'));

                    if ($change)
                    {
                        // if the password was successfully changed
                        $this->session->set_flashdata('message', $this->ion_auth->messages());
                        redirect("auth/login", 'refresh');
                    }
                    else
                    {
                        $this->session->set_flashdata('message', $this->ion_auth->errors());
                        redirect('auth/reset_password/' . $code, 'refresh');
                    }
                }
            }
        }
        else
        {
            // if the code is invalid then send them back to the forgot password page
            $this->session->set_flashdata('message', $this->ion_auth->errors());
            redirect("auth/forgot_password", 'refresh');
        }
    }


    // activate the user
    function activate($id, $code=false)
    {
        if ($code !== false)
        {
            $activation = $this->ion_auth->activate($id, $code);
        }
        else if ($this->ion_auth->is_admin())
        {
            $activation = $this->ion_auth->activate($id);
        }

        if ($activation)
        {
            // redirect them to the auth page
            $this->session->set_flashdata('message', $this->ion_auth->messages());
            redirect("auth", 'refresh');
        }
        else
        {
            // redirect them to the forgot password page
            $this->session->set_flashdata('message', $this->ion_auth->errors());
            redirect("auth/forgot_password", 'refresh');
        }
    }

    // deactivate the user
    function deactivate($id = NULL)
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin())
        {
            // redirect them to the home page because they must be an administrator to view this
            return show_error('You must be an administrator to view this page.');
        }

        if($this->_require_second_password() !== TRUE)
        {
            return FALSE;
        }

        $id = (int) $id;

        $this->load->library('form_validation');
        $this->form_validation->set_rules('confirm', $this->lang->line('deactivate_validation_confirm_label'), 'required');
        $this->form_validation->set_rules('id', $this->lang->line('deactivate_validation_user_id_label'), 'required|alpha_numeric');

        if ($this->form_validation->run() == FALSE)
        {
            // insert csrf check
            $this->data['csrf'] = $this->_get_csrf_nonce();
            $this->data['user'] = $this->ion_auth->user($id)->row();

            $this->_render_page('auth/deactivate_user', $this->data);
        }
        else
        {
            // do we really want to deactivate?
            if ($this->input->post('confirm') == 'yes')
            {
                // do we have a valid request?
                if ($this->_valid_csrf_nonce() === FALSE || $id != $this->input->post('id'))
                {
                    show_error($this->lang->line('error_csrf'));
                }

                // do we have the right userlevel?
                if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin())
                {
                    $this->ion_auth->deactivate($id);
                }
            }

            // redirect them back to the auth page
            redirect('auth', 'refresh');
        }
    }

    // create a new user
    function create_user()
    {
        $this->data['title'] = "Create User";

        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin())
        {
            redirect('auth', 'refresh');
        }

        $tables = $this->config->item('tables','ion_auth');
        $identity_column = $this->config->item('identity','ion_auth');
        $this->data['identity_column'] = $identity_column;

        // validate form input
        $this->form_validation->set_rules('first_name', $this->lang->line('create_user_validation_fname_label'), 'required');
        $this->form_validation->set_rules('last_name', $this->lang->line('create_user_validation_lname_label'), 'required');
        $this->form_validation->set_rules('melli', $this->lang->line('create_user_validation_melli_label'), 'required');
        $this->form_validation->set_rules('address', $this->lang->line('create_user_validation_address_label'), 'required');
        if($identity_column!=='email')
        {
            $this->form_validation->set_rules('identity',$this->lang->line('create_user_validation_identity_label'),'required|is_unique['.$tables['users'].'.'.$identity_column.']');
            $this->form_validation->set_rules('email', $this->lang->line('create_user_validation_email_label'), 'required|valid_email');
        }
        else
        {
            $this->form_validation->set_rules('email', $this->lang->line('create_user_validation_email_label'), 'required|valid_email|is_unique[' . $tables['users'] . '.email]');
        }
        $this->form_validation->set_rules('phone', $this->lang->line('create_user_validation_phone_label'), 'trim');
        $this->form_validation->set_rules('mobile', $this->lang->line('create_user_validation_mobile_label'), 'trim');
        $this->form_validation->set_rules('password', $this->lang->line('create_user_validation_password_label'), 'required|min_length[' . $this->config->item('min_password_length', 'ion_auth') . ']|max_length[' . $this->config->item('max_password_length', 'ion_auth') . ']|matches[password_confirm]');
        $this->form_validation->set_rules('password_confirm', $this->lang->line('create_user_validation_password_confirm_label'), 'required');

        if ($this->form_validation->run() == true)
        {
            $email    = strtolower($this->input->post('email'));
            $identity = ($identity_column==='email') ? $email : $this->input->post('identity');
            $password = $this->input->post('password');

            $additional_data = array(
                'first_name' => $this->input->post('first_name'),
                'last_name'  => $this->input->post('last_name'),
                'melli'      => $this->input->post('melli'),
                'address'    => $this->input->post('address'),
                'mobile'     => $this->input->post('mobile'),
                'phone'      => $this->input->post('phone'),
            );
        }
        if ($this->form_validation->run() == true && $this->ion_auth->register($identity, $password, $email, $additional_data))
        {
            // check to see if we are creating the user
            // redirect them back to the admin page
            $this->session->set_flashdata('message', $this->ion_auth->messages());
            redirect("auth", 'refresh');
        }
        else
        {
            // display the create user form
            // set the flash data error message if there is one
            $this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));

            $this->data['first_name'] = array(
                'name'  => 'first_name',
                'id'    => 'first_name',
                'type'  => 'text',
                'value' => $this->form_validation->set_value('first_name'),
            );
            $this->data['last_name'] = array(
                'name'  => 'last_name',
                'id'    => 'last_name',
                'type'  => 'text',
                'value' => $this->form_validation->set_value('last_name'),
            );
            $this->data['melli'] = array(
                'name'  => 'melli',
                'id'    => 'melli',
                'type'  => 'text',
                'value' => $this->form_validation->set_value('melli'),
            );
            $this->data['address'] = array(
                'name'  => 'address',
                'id'    => 'address',
                'type'  => 'text',
                'value' => $this->form_validation->set_value('address'),
            );
            $this->data['identity'] = array(
                'name'  => 'identity',
                'id'    => 'identity',
                'type'  => 'text',
                'value' => $this->form_validation->set_value('identity'),
            );
            $this->data['email'] = array(
                'name'  => 'email',
                'id'    => 'email',
                'type'  => 'text',
                'value' => $this->form_validation->set_value('email'),
            );
            $this->data['mobile'] = array(
                'name'  => 'mobile',
                'id'    => 'mobile',
                'type'  => 'text',
                'value' => $this->form_validation->set_value('mobile'),
            );
            $this->data['phone'] = array(
                'name'  => 'phone',
                'id'    => 'phone',
                'type'  => 'text',
                'value' => $this->form_validation->set_value('phone'),
            );
            $this->data['password'] = array(
                'name'  => 'password',
                'id'    => 'password',
                'type'  => 'password',
                'value' => $this->form_validation->set_value('password'),
            );
            $this->data['password_confirm'] = array(
                'name'  => 'password_confirm',
                'id'    => 'password_confirm',
                'type'  => 'password',
                'value' => $this->form_validation->set_value('password_confirm'),
            );

            $this->_render_page('auth/create_user', $this->data);
        }
    }

    // edit a user
    function edit_user($id=null)
    {
        $this->data['title'] = "Edit User";

        if (!$this->ion_auth->logged_in() OR ! $this->ion_auth->is_admin() OR intval($this->ion_auth->get_user_id()) !== 1 OR $id == null)
        {
            redirect('auth', 'refresh');
        }

        if($this->_require_second_password() !== TRUE)
        {
            return FALSE;
        }

        $user = $this->ion_auth->user($id)->row();
        $groups=$this->ion_auth->groups()->result_array();
        $currentGroups = $this->ion_auth->get_users_groups($id)->result();

        // validate form input
        $this->form_validation->set_rules('first_name', $this->lang->line('edit_user_validation_fname_label'), 'required');
        $this->form_validation->set_rules('last_name', $this->lang->line('edit_user_validation_lname_label'), 'required');
        $this->form_validation->set_rules('melli', $this->lang->line('edit_user_validation_melli_label'), 'required');
        $this->form_validation->set_rules('address', $this->lang->line('edit_user_validation_address_label'), 'required');
        $this->form_validation->set_rules('phone', $this->lang->line('edit_user_validation_phone_label'), 'required');
        $this->form_validation->set_rules('mobile', $this->lang->line('edit_user_validation_mobile_label'), 'required');
        $this->form_validation->set_rules('email', $this->lang->line('edit_user_validation_email_label'), 'required');
        $this->form_validation->set_rules('verify_name', $this->lang->line('edit_user_validation_verify_name_label'), 'required');
        $this->form_validation->set_rules('verify_address', $this->lang->line('edit_user_validation_verify_address_label'), 'required');
        $this->form_validation->set_rules('verify_melli', $this->lang->line('edit_user_validation_verify_melli_label'), 'required');
        $this->form_validation->set_rules('verify_mobile', $this->lang->line('edit_user_validation_verify_mobile_label'), 'required');
        $this->form_validation->set_rules('verify_phone', $this->lang->line('edit_user_validation_verify_phone_label'), 'required');
        $this->form_validation->set_rules('allow_exchange', $this->lang->line('edit_user_validation_allow_exchange_label'), 'required');

        if (isset($_POST) && !empty($_POST) && !isset($_POST['delete_yes']))
        {
            // do we have a valid request?
            if ($this->_valid_csrf_nonce() === FALSE || $id != $this->input->post('id'))
            {
                show_error($this->lang->line('error_csrf'));
            }

            // update the password if it was posted
            if ($this->input->post('password'))
            {
                $this->form_validation->set_rules('password', $this->lang->line('edit_user_validation_password_label'), 'required|min_length[' . $this->config->item('min_password_length', 'ion_auth') . ']|max_length[' . $this->config->item('max_password_length', 'ion_auth') . ']|matches[password_confirm]');
                $this->form_validation->set_rules('password_confirm', $this->lang->line('edit_user_validation_password_confirm_label'), 'required');
            }

            if ($this->form_validation->run() === TRUE)
            {
                $data = array(
                    'first_name' => $this->input->post('first_name'),
                    'last_name'  => $this->input->post('last_name'),
                    'melli'      => $this->input->post('melli'),
                    'address'    => $this->input->post('address'),
                    'mobile'     => $this->input->post('mobile'),
                    'phone'      => $this->input->post('phone'),
                    'email'      => $this->input->post('email'),
                    'verify_name'    => $this->input->post('verify_name'),
                    'verify_address' => $this->input->post('verify_address'),
                    'verify_melli'   => $this->input->post('verify_melli'),
                    'verify_mobile'  => $this->input->post('verify_mobile'),
                    'verify_phone'   => $this->input->post('verify_phone'),
                    'allow_exchange' => $this->input->post('allow_exchange'),
                );

                // update the password if it was posted
                if ($this->input->post('password'))
                {
                    $data['password'] = $this->input->post('password');
                }



                // Only allow updating groups if user is admin
                if ($this->ion_auth->is_admin())
                {
                    //Update the groups user belongs to
                    $groupData = $this->input->post('groups');

                    if (isset($groupData) && !empty($groupData)) {

                        $this->ion_auth->remove_from_group('', $id);

                        foreach ($groupData as $grp) {
                            $this->ion_auth->add_to_group($grp, $id);
                        }

                    }
                }

                // check to see if we are updating the user
                if($this->ion_auth->update($user->id, $data))
                {
                    // redirect them back to the admin page if admin, or to the base url if non admin
                    $this->session->set_flashdata('message', $this->ion_auth->messages() );
                    if ($this->ion_auth->is_admin())
                    {
                        redirect('auth', 'refresh');
                    }
                    else
                    {
                        redirect('/', 'refresh');
                    }

                }
                else
                {
                    // redirect them back to the admin page if admin, or to the base url if non admin
                    $this->session->set_flashdata('message', $this->ion_auth->errors() );
                    if ($this->ion_auth->is_admin())
                    {
                        redirect('auth', 'refresh');
                    }
                    else
                    {
                        redirect('/', 'refresh');
                    }

                }

            }
        }

        if (isset($_POST['delete_yes']) && $_POST['delete_yes'] == 'YES')
        {
            $this->db->where('id', $id);
            $this->db->delete('app_users');
            $this->session->set_flashdata('message', 'کاربر مورد نظر حذف شد');
            redirect('auth/manager', 'refresh');

        }

        // display the edit user form
        $this->data['csrf'] = $this->_get_csrf_nonce();

        // set the flash data error message if there is one
        $this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));

        // pass the user to the view
        $this->data['user'] = $user;
        $this->data['groups'] = $groups;
        $this->data['currentGroups'] = $currentGroups;

        $this->data['delete_yes'] = array(
            'name'  => 'delete_yes',
            'id'    => 'delete_yes',
            'type'  => 'text',
            'autocomplete' => 'off',
            'value' => ''
        );

        $this->data['first_name'] = array(
            'name'  => 'first_name',
            'id'    => 'first_name',
            'type'  => 'text',
            'value' => $this->form_validation->set_value('first_name', $user->first_name),
        );
        $this->data['last_name'] = array(
            'name'  => 'last_name',
            'id'    => 'last_name',
            'type'  => 'text',
            'value' => $this->form_validation->set_value('last_name', $user->last_name),
        );
        $this->data['melli'] = array(
            'name'  => 'melli',
            'id'    => 'melli',
            'type'  => 'text',
            'value' => $this->form_validation->set_value('melli', $user->melli),
        );
        $this->data['address'] = array(
            'name'  => 'address',
            'id'    => 'address',
            'type'  => 'text',
            'value' => $this->form_validation->set_value('address', $user->address),
        );
        $this->data['mobile'] = array(
            'name'  => 'mobile',
            'id'    => 'mobile',
            'type'  => 'text',
            'value' => $this->form_validation->set_value('mobile', $user->mobile),
        );
        $this->data['phone'] = array(
            'name'  => 'phone',
            'id'    => 'phone',
            'type'  => 'text',
            'value' => $this->form_validation->set_value('phone', $user->phone),
        );
        $this->data['email'] = array(
            'name'  => 'email',
            'id'    => 'email',
            'type'  => 'text',
            'value' => $this->form_validation->set_value('email', $user->email),
        );
        $this->data['password'] = array(
            'name' => 'password',
            'id'   => 'password',
            'type' => 'password'
        );
        $this->data['password_confirm'] = array(
            'name' => 'password_confirm',
            'id'   => 'password_confirm',
            'type' => 'password'
        );
        $this->data['verify_name'] = array(
            'name'  => 'verify_name',
            'id'    => 'verify_name',
            'value' => $this->form_validation->set_value('verify_name', $user->verify_name),
        );
        $this->data['verify_address'] = array(
            'name'  => 'verify_address',
            'id'    => 'verify_address',
            'value' => $this->form_validation->set_value('verify_address', $user->verify_address),
        );
        $this->data['verify_melli'] = array(
            'name'  => 'verify_melli',
            'id'    => 'verify_melli',
            'value' => $this->form_validation->set_value('verify_melli', $user->verify_melli),
        );
        $this->data['verify_mobile'] = array(
            'name'  => 'verify_mobile',
            'id'    => 'verify_mobile',
            'value' => $this->form_validation->set_value('verify_mobile', $user->verify_mobile),
        );
        $this->data['verify_phone'] = array(
            'name'  => 'verify_phone',
            'id'    => 'verify_phone',
            'value' => $this->form_validation->set_value('verify_phone', $user->verify_phone),
        );
        $this->data['allow_exchange'] = array(
            'name'  => 'allow_exchange',
            'id'    => 'allow_exchange',
            'value' => $this->form_validation->set_value('allow_exchange', $user->allow_exchange),
        );

        // Check for Document Verify Image
        $user_id = $id;
        $this->data['document_image'] = base_url("./program/verify_scans/no_image.png");

        $file_img_url = "program/verify_scans/document_userid_{$user_id}";
        $this->data['file_ext'] = 'none';

        if (file_exists(FCPATH."{$file_img_url}.jpg"))
        {
            $this->data['document_image'] = base_url("./{$file_img_url}.jpg");
            $this->data['file_ext'] = 'jpg';
        }

        elseif (file_exists(FCPATH."{$file_img_url}.png"))
        {
            $this->data['document_image'] = base_url("./{$file_img_url}.png");
            $this->data['file_ext'] = 'png';
        }

        elseif (file_exists(FCPATH."{$file_img_url}.gif"))
        {
            $this->data['document_image'] = base_url("./{$file_img_url}.gif");
            $this->data['file_ext'] = 'gif';
        }

        elseif (file_exists(FCPATH."{$file_img_url}.jpeg"))
        {
            $this->data['document_image'] = base_url("./{$file_img_url}.jpeg");
            $this->data['file_ext'] = 'jpeg';
        }

        $this->data['img_safe'] = TRUE;
        $this->data['checked_img'] = FALSE;

        if ($this->data['file_ext'] !== 'none')
        {
            $full_img_url = FCPATH."{$file_img_url}.{$this->data['file_ext']}";

            if ( ! file_exists($full_img_url) OR $this->security->xss_clean($full_img_url, TRUE) === FALSE)
            {
                // Not Safe
                $this->data['img_safe'] = FALSE;
                $this->data['document_image'] = base_url("./program/verify_scans/no_image.png");
            }
            else
            {
                $this->data['checked_img'] = TRUE;
            }
        }

        $this->_render_page('auth/edit_user', $this->data);
    }

    // create a new group
    function create_group()
    {
        $this->data['title'] = $this->lang->line('create_group_title');

        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin())
        {
            redirect('auth', 'refresh');
        }

        if($this->_require_second_password() !== TRUE)
        {
            return FALSE;
        }

        // validate form input
        $this->form_validation->set_rules('group_name', $this->lang->line('create_group_validation_name_label'), 'required|alpha_dash');

        if ($this->form_validation->run() == TRUE)
        {
            $new_group_id = $this->ion_auth->create_group($this->input->post('group_name'), $this->input->post('description'));
            if($new_group_id)
            {
                // check to see if we are creating the group
                // redirect them back to the admin page
                $this->session->set_flashdata('message', $this->ion_auth->messages());
                redirect("auth", 'refresh');
            }
        }
        else
        {
            // display the create group form
            // set the flash data error message if there is one
            $this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));

            $this->data['group_name'] = array(
                'name'  => 'group_name',
                'id'    => 'group_name',
                'type'  => 'text',
                'value' => $this->form_validation->set_value('group_name'),
            );
            $this->data['description'] = array(
                'name'  => 'description',
                'id'    => 'description',
                'type'  => 'text',
                'value' => $this->form_validation->set_value('description'),
            );

            $this->_render_page('auth/create_group', $this->data);
        }
    }

    // edit a group
    function edit_group($id)
    {
        // bail if no group id given
        if(!$id || empty($id))
        {
            redirect('auth', 'refresh');
        }

        if($this->_require_second_password() !== TRUE)
        {
            return FALSE;
        }

        $this->data['title'] = $this->lang->line('edit_group_title');

        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin())
        {
            redirect('auth', 'refresh');
        }

        $group = $this->ion_auth->group($id)->row();

        // validate form input
        $this->form_validation->set_rules('group_name', $this->lang->line('edit_group_validation_name_label'), 'required|alpha_dash');

        if (isset($_POST) && !empty($_POST))
        {
            if ($this->form_validation->run() === TRUE)
            {
                $group_update = $this->ion_auth->update_group($id, $_POST['group_name'], $_POST['group_description']);

                if($group_update)
                {
                    $this->session->set_flashdata('message', $this->lang->line('edit_group_saved'));
                }
                else
                {
                    $this->session->set_flashdata('message', $this->ion_auth->errors());
                }
                redirect("auth", 'refresh');
            }
        }

        // set the flash data error message if there is one
        $this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));

        // pass the user to the view
        $this->data['group'] = $group;

        $readonly = $this->config->item('admin_group', 'ion_auth') === $group->name ? 'readonly' : '';

        $this->data['group_name'] = array(
            'name'    => 'group_name',
            'id'      => 'group_name',
            'type'    => 'text',
            'value'   => $this->form_validation->set_value('group_name', $group->name),
            $readonly => $readonly,
        );
        $this->data['group_description'] = array(
            'name'  => 'group_description',
            'id'    => 'group_description',
            'type'  => 'text',
            'value' => $this->form_validation->set_value('group_description', $group->description),
        );

        $this->_render_page('auth/edit_group', $this->data);
    }










    // redirect if needed, otherwise display the user list
    function prices()
    {
        if ( ! $this->ion_auth->is_admin() OR intval($this->ion_auth->get_user_id()) !== 1)
        {
            return show_error('صفحه مورد نظر یافت نشد');
            die();
        }

        if($this->_require_second_password() !== TRUE)
        {
            return FALSE;
        }

        // set the flash data error message if there is one
        $this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');

        //list the users
        $this->db->select('*');
        $this->data['prices'] = $this->db->get('app_prices')->result();
        $this->_render_page('auth/prices', $this->data);
    }




    // edit prices
    function edit_prices($id=null)
    {
        // bail if no id given
        if(!$id || empty($id) || intval($id) < 1 || !is_numeric($id))
        {
            redirect('auth', 'refresh');
        }

        $this->data['title'] = $this->lang->line('edit_group_title');

        if ( ! $this->ion_auth->is_admin() OR intval($this->ion_auth->get_user_id()) !== 1)
        {
            return show_error('صفحه مورد نظر یافت نشد');
            die();
        }

        if($this->_require_second_password() !== TRUE)
        {
            return FALSE;
        }

        $this->db->select('*');
        $this->db->where('id', $id);
        $prices = $this->db->get('app_prices')->row();
        // validate form input

        // If BTC
        if ($prices->english_name == 'bitcoin')
        {
            $this->form_validation->set_rules('bitcoin_buy_each_usd', 'قیمت خرید', 'trim|required|integer');
            $this->form_validation->set_rules('bitcoin_sell_each_usd', 'قیمت فروش', 'trim|required|integer');
        }
        else
        {
            $this->form_validation->set_rules('buy_price', 'قیمت خرید', 'trim|required|integer');
            $this->form_validation->set_rules('sell_price', 'قیمت فروش', 'trim|required|integer');
        }
        $this->form_validation->set_rules('available', 'موجودی', 'trim|required|numeric');
        $this->form_validation->set_rules('description', 'توضیحات', 'htmlspecialchars');
        $this->form_validation->set_rules('contact', 'تماس بگیرید', 'integer|less_than_equal_to[1]|greater_than_equal_to[0]');
        $this->form_validation->set_rules('sell_min_amount', 'حداقل فروش به کاربر', 'trim|required|numeric');
        $this->form_validation->set_rules('sell_max_amount', 'حداکثر فروش به کاربر', 'trim|required|numeric');
        $this->form_validation->set_rules('buy_min_amount', 'حداقل خریداز کاربر', 'trim|required|numeric');
        $this->form_validation->set_rules('buy_max_amount', 'حداکثر خریداز کاربر', 'trim|required|numeric');
        $this->form_validation->set_rules('temp_disable', 'موقت غیر فعال', 'integer|less_than_equal_to[1]|greater_than_equal_to[0]');
        $this->form_validation->set_rules('active', 'فعال', 'integer|less_than_equal_to[1]|greater_than_equal_to[0]');

        if (isset($_POST) && !empty($_POST))
        {
            if ($this->form_validation->run() === TRUE)
            {
                if (!isset($_POST['contact']) OR intval($_POST['contact'])!==1){$do_contact=0;}else{$do_contact=1;}
                if (!isset($_POST['temp_disable']) OR intval($_POST['temp_disable'])!==1){$temp_disable=0;}else{$temp_disable=1;}
                if (!isset($_POST['active']) OR intval($_POST['active'])!==1){$do_active=0;}else{$do_active=1;}
                if (isset($_POST['description']))
                {
                    $description = htmlspecialchars($this->input->post('description'));
                }
                else{
                    $description = '';
                }
                // Bitcoin or Not
                if ($prices->english_name == 'bitcoin')
                {
                    $data = array(
                        'bitcoin_buy_each_usd'   => $_POST['bitcoin_buy_each_usd'],
                        'bitcoin_sell_each_usd'  => $_POST['bitcoin_sell_each_usd'],
                        'available'   => $_POST['available'],
                        'contact'     => $do_contact,
                        'temp_disable'      => $temp_disable,
                        'active'      => $do_active,
                        'description' => $description,
                        'sell_min_amount' => $_POST['sell_min_amount'],
                        'sell_max_amount' => $_POST['sell_max_amount'],
                        'buy_min_amount'  => $_POST['buy_min_amount'],
                        'buy_max_amount'  => $_POST['buy_max_amount']
                    );
                }
                else
                {
                    $data = array(
                        'buy_price'   => $_POST['buy_price'],
                        'sell_price'  => $_POST['sell_price'],
                        'available'   => $_POST['available'],
                        'contact'     => $do_contact,
                        'temp_disable'      => $temp_disable,
                        'active'      => $do_active,
                        'description' => $description,
                        'sell_min_amount' => $_POST['sell_min_amount'],
                        'sell_max_amount' => $_POST['sell_max_amount'],
                        'buy_min_amount'  => $_POST['buy_min_amount'],
                        'buy_max_amount'  => $_POST['buy_max_amount']
                    );
                }

                $this->db->where('id', $id);
                $price_update = $this->db->update('app_prices', $data);

                if($price_update)
                {
                    $this->session->set_flashdata('message', 'با موفقیت ذخیره شد');
                }
                else
                {
                    $this->session->set_flashdata('message', 'خطا در تغییر قیمت');
                }
                redirect("auth/prices", 'refresh');
            }
        }

        // set the flash data error message if there is one
        $this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));

        // pass the user to the view
        $this->data['prices'] = $prices;

        $this->data['name'] = $prices->persian_name;

        if ($prices->english_name == 'bitcoin')
        {
            $this->data['buy_price'] = array(
                'name'  => 'bitcoin_buy_each_usd',
                'id'    => 'bitcoin_buy_each_usd',
                'type'  => 'text',
                'value' => $this->form_validation->set_value('bitcoin_buy_each_usd', $prices->bitcoin_buy_each_usd),
            );

            $this->data['sell_price'] = array(
                'name'  => 'bitcoin_sell_each_usd',
                'id'    => 'bitcoin_sell_each_usd',
                'type'  => 'text',
                'value' => $this->form_validation->set_value('bitcoin_sell_each_usd', $prices->bitcoin_sell_each_usd),
            );
        }
        else
        {
            $this->data['buy_price'] = array(
                'name'  => 'buy_price',
                'id'    => 'buy_price',
                'type'  => 'text',
                'value' => $this->form_validation->set_value('buy_price', $prices->buy_price),
            );

            $this->data['sell_price'] = array(
                'name'  => 'sell_price',
                'id'    => 'sell_price',
                'type'  => 'text',
                'value' => $this->form_validation->set_value('sell_price', $prices->sell_price),
            );
        }

        $this->data['available'] = array(
            'name'  => 'available',
            'id'    => 'available',
            'type'  => 'text',
            'value' => $this->form_validation->set_value('available', $prices->available),
        );

        if(intval($prices->contact)===1){$checked=TRUE;}else{$checked=FALSE;}
        $this->data['contact'] = array(
            'name'  => 'contact',
            'id'    => 'contact',
            'checked' => $checked,
            'value'   => '1'
        );

        if(intval($prices->active)===1){$checked=TRUE;}else{$checked=FALSE;}
        $this->data['active'] = array(
            'name'    => 'active',
            'id'      => 'active',
            'checked' => $checked,
            'value'   => '1'
        );

        if(intval($prices->temp_disable)===1){$checked=TRUE;}else{$checked=FALSE;}
        $this->data['temp_disable'] = array(
            'name'    => 'temp_disable',
            'id'      => 'temp_disable',
            'checked' => $checked,
            'value'   => '1'
        );

        $this->data['sell_min_amount'] = array(
            'name'  => 'sell_min_amount',
            'id'    => 'sell_min_amount',
            'type'  => 'text',
            'value' => $this->form_validation->set_value('sell_min_amount', $prices->sell_min_amount),
        );

        $this->data['sell_max_amount'] = array(
            'name'  => 'sell_max_amount',
            'id'    => 'sell_max_amount',
            'type'  => 'text',
            'value' => $this->form_validation->set_value('sell_max_amount', $prices->sell_max_amount),
        );

        $this->data['buy_min_amount'] = array(
            'name'  => 'buy_min_amount',
            'id'    => 'buy_min_amount',
            'type'  => 'text',
            'value' => $this->form_validation->set_value('buy_min_amount', $prices->buy_min_amount),
        );

        $this->data['buy_max_amount'] = array(
            'name'  => 'buy_max_amount',
            'id'    => 'buy_max_amount',
            'type'  => 'text',
            'value' => $this->form_validation->set_value('buy_max_amount', $prices->buy_max_amount),
        );

        $this->data['description'] = array(
            'name'  => 'description',
            'id'    => 'description',
            'value' => $this->form_validation->set_value('description', $prices->description)
        );

        $this->_render_page('auth/edit_prices', $this->data);
    }






    // send news
    function post_news()
    {
        if ( ! $this->ion_auth->is_admin() OR intval($this->ion_auth->get_user_id()) !== 1)
        {
            return show_error('صفحه مورد نظر یافت نشد');
            die();
        }

        if($this->_require_second_password() !== TRUE)
        {
            return FALSE;
        }

        $this->load->library('form_validation');
        $this->form_validation->set_rules('title', "Title", 'required|min_length[2]|max_length[128]|encode_php_tags|htmlspecialchars');
        $this->form_validation->set_rules('news', "News text", 'required|min_length[2]|max_length[2000]|encode_php_tags|htmlspecialchars');

        $this->data['news_array'] = $this->db->query("SELECT * FROM app_news ORDER BY id DESC")->result_array();

        if ($this->form_validation->run() == FALSE)
        {
            // insert csrf check
            $this->data['csrf'] = $this->_get_csrf_nonce();


        }
        else
        {
            if ($this->_valid_csrf_nonce() === FALSE)
            {
                show_error($this->lang->line('error_csrf'));
            }
            else
            {
                $title = $this->input->post('title');
                $news  = $this->input->post('news');
                // insert news
                $data = array(
                    'title' => $title,
                    'news'  => $news
                );

                $insert = $this->db->insert('app_news', $data);

                // display
                if ($insert && $this->db->affected_rows() > 0)
                {
                    $this->session->set_flashdata('message', "اخبار ارسال شد");
                }
                else
                {
                    $this->session->set_flashdata('message', "به نظر مشکلی هست لطفا بررسی کنید‌آیا خبر ارسال شده");
                }
                redirect('auth/post_news?sent', 'refresh');
            }
        }

        // set the flash data error message if there is one
        $this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));

        $this->_render_page('auth/post_news', $this->data);
    }




    // delete news
    function news_delete($id = null)
    {
        if ( ! $this->ion_auth->is_admin() OR intval($this->ion_auth->get_user_id()) !== 1)
        {
            return show_error('صفحه مورد نظر یافت نشد');
            die();
        }

        if($this->_require_second_password() !== TRUE)
        {
            return FALSE;
        }

        $news_id = intval($id);

        $delete = $this->db->query("DELETE FROM app_news WHERE id={$news_id}");

        if ($delete)
        {
            $this->session->set_flashdata('message', "خبر حذف شد");
        }
        else
        {
            // set the flash data error message if there is one
            $this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));
        }

        redirect('auth/post_news', 'refresh');
    }



    // admin security IP ban list etc
    function admin_security()
    {
        if (!$this->ion_auth->is_admin() OR intval($this->ion_auth->get_user_id()) !== 1)
        {
            // redirect them to the logout because they must be an administrator to view this
            return show_error('صفحه مورد نظر یافت نشد');
        }
        else
        {
            // Second password
            if($this->_require_second_password() !== TRUE)
            {
                return FALSE;
            }

            $this->load->library('form_validation');

            // Delete from Block?
            if ($this->input->post('action') === 'delete_ip')
            {
                if ($this->_valid_csrf_nonce() === FALSE)
                {
                    show_error($this->lang->line('error_csrf'));
                }
                else
                {
                    // Delete

                    // Validation Rule
                    $this->form_validation->set_rules('ip_address', 'آی پی آدرس', 'required|valid_ip');

                    // Validate
                    if ($this->form_validation->run() !== FALSE)
                    {
                        $ip = trim(htmlspecialchars($this->input->post('ip_address')));
                        $delete = $this->db->where('ip_address', $ip)->delete('app_ip_ban');

                        if ($delete && $this->db->affected_rows() > 0)
                        {
                            $this->session->set_flashdata('message', 'آی پی پاک شد');
                        }
                        else
                        {
                            $this->session->set_flashdata('message', 'پیدا نشد');
                        }
                    }
                }
            }

            // Add to Block?
            if ($this->input->post('action') === 'add_ip')
            {
                if ($this->_valid_csrf_nonce() === FALSE)
                {
                    show_error($this->lang->line('error_csrf'));
                }
                else
                {
                    // Add

                    // Validation Rule
                    $this->form_validation->set_rules('ip_address', 'آی پی آدرس', 'required|valid_ip');

                    // Validate
                    if ($this->form_validation->run() !== FALSE)
                    {
                        $ip = trim(htmlspecialchars($this->input->post('ip_address')));
                        $query = $this->db->query("INSERT INTO app_ip_ban (ip_address, page, ban) VALUES (".$this->db->escape($ip).",'Manual',1)");

                        if ($query && $this->db->affected_rows() > 0)
                        {
                            $this->session->set_flashdata('message', 'به لیست بلاک اضافه شد');
                        }
                        else
                        {
                            $this->session->set_flashdata('message', 'خطا');
                        }
                    }
                }
            }

            // List IPs
            $this->data['ip_ban'] = $this->db->order_by('count', 'DESC')->get('app_ip_ban', 100, 0)->result_array();

            $this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));
            $this->data['csrf'] = $this->_get_csrf_nonce();
            $this->_render_page('auth/admin_security', $this->data);
        }
    }



    function pending_exchanges()
    {
        if ( ! $this->ion_auth->is_admin() OR intval($this->ion_auth->get_user_id()) !== 1)
        {
            return show_error('صفحه مورد نظر یافت نشد');
            die();
        }

        if($this->_require_second_password() !== TRUE)
        {
            return FALSE;
        }

        if (isset($_POST['delete_yes']) && $_POST['delete_yes'] === 'YES')
        {
            if ($this->_valid_csrf_nonce() === FALSE)
            {
                show_error($this->lang->line('error_csrf'));
            }
            else
            {
                // DB Transaction
                $this->db->trans_start();
                $this->db->query('INSERT INTO app_exchanges_archive SELECT * FROM app_exchanges WHERE completed = 0');
                $this->db->query('DELETE FROM app_exchanges WHERE completed = 0');
                $this->db->trans_complete();

                if ($this->db->trans_status() === FALSE)
                {
                    $this->session->set_flashdata('message', "خطا هنگام آرشیو کردن");
                }
            }
        }

        $start                   = 0;
        $this->data['per_page']  = 50;
        $this->data['default_archive_per_page'] = 500;

        if ($this->uri->segment(3) && is_numeric($this->uri->segment(3)))
        {
            $start = intval($this->uri->segment(3));
        }

        $this->db->select('*');
        $this->db->where('completed', 0);
        $this->db->order_by('id', 'DESC');

        // Archive or Pending?
        $this->data['is_archive'] = FALSE;
        if ($this->uri->segment(3) === 'archive')
        {
            // Display Archive
            $this->data['is_archive'] = TRUE;
            $this->data['archive_per_page'] = $this->data['default_archive_per_page'];
            if (is_numeric($this->uri->segment(4)) && $this->uri->segment(4) < 25000 && $this->uri->segment(4) >= 0)
            {
                $this->data['archive_per_page'] = $this->uri->segment(4);
            }

            $this->data['transactions'] = $this->db->get('app_exchanges_archive', $this->data['archive_per_page'], 0)->result_array();
            $this->data['count']        = $this->db->query("SELECT IFNULL(count(id),0) AS counted FROM `app_exchanges_archive` WHERE completed=0")->row()->counted;
        }
        else
        {
            // Not Archive
            $this->data['count']        = $this->db->query("SELECT IFNULL(count(id),0) AS counted FROM `app_exchanges` WHERE completed=0")->row()->counted;
            $this->data['start']        = $start;
            $this->data['transactions'] = $this->db->get('app_exchanges', $this->data['per_page'], $start)->result_array();
        }

        $this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));

        foreach($this->data['transactions'] as $key => $trans)
        {
            // Get Name
            $this->db->select('*');
            $this->db->where('id', $trans['user_id']);
            $get_name = $this->db->get('app_users')->row();
            $curr = $trans['ecurrency'].'_acc';
            if ( ! isset($get_name->first_name))
            {
                $this->data['transactions'][$key]['user_name'] = 'کاربر پاک شده است';
            }
            else
            {
                $this->data['transactions'][$key]['user_name'] = $get_name->first_name . ' ' . $get_name->last_name;
            }

            if (isset($get_name->$curr) && strlen($get_name->$curr) > 1)
            {
                $this->data['transactions'][$key]['currency']  = $get_name->$curr;
            }
            else
            {
                $this->data['transactions'][$key]['currency']  = 'کاربر ذخیره نکرده';
            }


            if($trans['ecurrency'] != 'bitcoin')
            {
                $this->data['transactions'][$key]['amount'] = $this->truncate_number($this->data['transactions'][$key]['amount']);
                $this->data['transactions'][$key]['unit']   = 'دلار';
            }
            else
            {
                $this->data['transactions'][$key]['unit']   = 'بیتکوین';
            }
        }

        $this->data['csrf'] = $this->_get_csrf_nonce();

        $this->data['delete_yes'] = array(
            'name'  => 'delete_yes',
            'id'    => 'delete_yes',
            'type'  => 'text',
            'autocomplete' => 'off',
            'value' => ''
        );

        $this->_render_page('auth/pending_exchanges', $this->data);
    }


    // Exchanges
    function exchanges()
    {
        if ( ! $this->ion_auth->is_admin() OR intval($this->ion_auth->get_user_id()) !== 1)
        {
            return show_error('صفحه مورد نظر یافت نشد');
            die();
        }

        if($this->_require_second_password() !== TRUE)
        {
            return FALSE;
        }

        $this->data['start']     = 0;
        $this->data['per_page']  = 100;

        $this->data['count'] = $this->db->query("SELECT IFNULL(count(id),0) AS counted FROM `app_exchanges` WHERE completed=1")->row()->counted;

        if ($this->uri->segment(3) && is_numeric($this->uri->segment(3)))
        {
            $this->data['start'] = intval($this->uri->segment(3));
        }
        elseif ($this->uri->segment(3) === 'all')
        {
            $this->data['start'] = 0;
            $this->data['per_page'] = 0;
        }

        $this->db->select('*');
        $this->db->where('completed', 1);
        $this->db->order_by('id', 'DESC');
        $this->data['transactions'] = $this->db->get('app_exchanges', $this->data['per_page'], $this->data['start'])->result_array();

        $this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));

        foreach($this->data['transactions'] as $key => $trans)
        {
            // Get Name
            $this->db->select('*');
            $this->db->where('id', $trans['user_id']);
            $get_name = $this->db->get('app_users')->row();

            if ( ! isset($get_name->first_name))
            {
                $this->data['transactions'][$key]['user_name'] = 'کاربر پاک شده است';
            }
            else
            {
                $this->data['transactions'][$key]['user_name'] = $get_name->first_name . ' ' . $get_name->last_name;
            }

            if($trans['ecurrency'] != 'bitcoin')
            {
                $this->data['transactions'][$key]['amount'] = $this->truncate_number($this->data['transactions'][$key]['amount']);
                $this->data['transactions'][$key]['unit']   = 'دلار';
            }
            else
            {
                $this->data['transactions'][$key]['unit']   = 'بیتکوین';
            }
        }

        $this->_render_page('auth/exchanges', $this->data);
    }


    // List user exchanges
    function user_exchanges($id)
    {
        if ( ! $this->ion_auth->is_admin() OR intval($this->ion_auth->get_user_id()) !== 1)
        {
            return show_error('صفحه مورد نظر یافت نشد');
            die();
        }

        if($this->_require_second_password() !== TRUE)
        {
            return FALSE;
        }

        $this->data['title'] = "User Exchanges";

        if ($this->uri->segment(3) != $id OR $this->uri->segment(3) < 1 OR ! is_numeric($this->uri->segment(3)))
        {
            redirect('auth', 'refresh');
        }

        $user_id = (int)$id;
        $this->data['count_completed'] = $this->db->query("SELECT IFNULL(count(id),0) AS counted FROM `app_exchanges` WHERE user_id = {$user_id} AND completed = 1")->row()->counted;
        $this->data['count_pending']   = $this->db->query("SELECT IFNULL(count(id),0) AS counted FROM `app_exchanges` WHERE user_id = {$user_id} AND completed = 0")->row()->counted;
        $this->data['count_archive']   = $this->db->query("SELECT IFNULL(count(id),0) AS counted FROM `app_exchanges_archive` WHERE user_id = {$user_id}")->row()->counted;

        $this->db->select('*');
        $this->db->where('user_id', $user_id);
        $this->db->order_by('id', 'DESC');
        $this->data['transactions'] = $this->db->get('app_exchanges')->result_array();

        $this->data['user'] = $this->ion_auth->user($id)->row();
        $this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));

        $this->_render_page('auth/user_exchanges', $this->data);
    }


    // redirect if needed, otherwise display the search user
    function search_exchanges()
    {
        if ( ! $this->ion_auth->is_admin() OR intval($this->ion_auth->get_user_id()) !== 1)
        {
            return show_error('صفحه مورد نظر یافت نشد');
            die();
        }

        if($this->_require_second_password() !== TRUE)
        {
            return FALSE;
        }

        $this->data['transactions'] = array();
        $this->data['found_search'] = 0;
        $this->data['search_display'] = '';

        // set the flash data error message if there is one
        $this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');

        $search_exchange_method = $this->input->post('search_exchange_method');
        $search_exchange_value  = $this->input->post('search_exchange_value');

        $search_exchange_from = $this->input->post('search_exchange_from');
        $search_exchange_to   = $this->input->post('search_exchange_to');

        if ($search_exchange_method && in_array($search_exchange_method, ['amount','rials','ip_address','batch','time_range','user_bank_info','transaction_id','amount_range','bank_transaction_reference_id']))
        {
            $method = $this->security->xss_clean($search_exchange_method);
        }

        if ($search_exchange_value &&  strlen($search_exchange_value) > 0)
        {
            $value = trim(htmlspecialchars($this->security->xss_clean($search_exchange_value)));
        }

        //list searched users
        if (isset($method) && isset($value))
        {
            // Ranged?
            if ($method === 'time_range' OR $method === 'amount_range')
            {
                if ($method === 'amount_range')
                {
                    // Amount Range
                    if (! is_numeric($search_exchange_from) OR ! is_numeric($search_exchange_to))
                    {
                        $search_exchange_from = 0;
                        $search_exchange_to = 0;
                    }

                    $where = array('amount >=' => $search_exchange_from, 'amount <=' => $search_exchange_to);
                }
                else
                {
                    // Date Range
                    $search_exchange_from = date("Y-m-d 00:00:00", strtotime(htmlspecialchars(trim($search_exchange_from))));
                    $search_exchange_to   = date("Y-m-d 23:59:59", strtotime(htmlspecialchars(trim($search_exchange_to))));

                    $where = array('date >=' => $search_exchange_from, 'date <=' => $search_exchange_to);
                }

                $this->db->select('*');
                $this->db->from('app_exchanges');
                $this->db->where($where);
                $this->data['transactions'] = $this->db->get()->result_array();

                $value = "($search_exchange_from - $search_exchange_to)";
            }
            // Fixed Value
            else
            {
                // Search by WHERE or LIKE?
                if($this->input->post('search_like_or_exact') === 'exactly')
                {
                    $this->db->where("$method", "$value");
                }
                else
                {
                    $this->db->like("$method", "$value");
                }
                $this->db->select('*');
                $this->db->order_by('id', 'DESC');
                $this->data['transactions'] = $this->db->get('app_exchanges')->result_array();
            }
            $this->data['search_display'] = "{$method} = {$value}";

            foreach ($this->data['transactions'] as $key => $trans)
            {
                $this->data['found_search']++;
                // Get Name
                $this->db->select('*');
                $this->db->where('id', $trans['user_id']);
                $get_name = $this->db->get('app_users')->row();
                $user_id = $trans['user_id'];
                $curr = $trans['ecurrency'].'_acc';

                if ( ! isset($get_name->first_name))
                {
                    $this->data['transactions'][$key]['user_name'] = 'کاربر پاک شده است';
                    $this->data['transactions'][$key]['email'] = 'کاربر پاک شده است';
                }
                else
                {
                    $this->data['transactions'][$key]['user_name'] = $get_name->first_name . ' ' . $get_name->last_name;
                    $this->data['transactions'][$key]['email'] = $get_name->email;
                }

                if (isset($get_name->$curr) && strlen($get_name->$curr) > 1)
                {
                    $this->data['transactions'][$key]['currency']  = $get_name->$curr;
                }
                else
                {
                    $this->data['transactions'][$key]['currency']  = 'کاربر ذخیره نکرده';
                }

                if($trans['ecurrency'] != 'bitcoin')
                {
                    $this->data['transactions'][$key]['amount'] = $this->truncate_number($this->data['transactions'][$key]['amount']);
                    $this->data['transactions'][$key]['unit']   = 'دلار';
                }
                else
                {
                    $this->data['transactions'][$key]['unit']   = 'بیتکوین';
                }
            }
        }

        $this->_render_page('auth/search_exchanges', $this->data);
    }


    // Edit Exchange
    function edit_exchange()
    {
        if ( ! $this->ion_auth->is_admin() OR intval($this->ion_auth->get_user_id()) !== 1)
        {
            return show_error('صفحه مورد نظر یافت نشد');
            die();
        }

        if($this->_require_second_password() !== TRUE)
        {
            return FALSE;
        }

        // Get segment
        $exchange_id = intval($this->uri->segment(3));
        if ($exchange_id < 1)
        {
            $this->session->set_flashdata('message', 'این اکسچنج یافت نشد');
            redirect("auth/pending_exchanges", 'refresh');
        }

        $this->db->select('*');
        $this->db->where('id', $exchange_id);
        $this->db->order_by('id', 'DESC');
        $this->data['transactions'] = $this->db->get('app_exchanges')->result_array();

        $this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));

        foreach($this->data['transactions'] as $key => $trans)
        {
            // Get Name
            $this->db->select('*');
            $this->db->where('id', $trans['user_id']);
            $get_name = $this->db->get('app_users')->row();
            $user_id = $trans['user_id'];

            $this->data['transactions'][$key]['user_name'] = $get_name->first_name . ' ' . $get_name->last_name;

            $this->data['transactions'][$key]['mellat'] = $get_name->mellat_acc;
            $this->data['transactions'][$key]['saman'] = $get_name->saman_acc;
            $this->data['transactions'][$key]['card'] = $get_name->card_acc;
            $this->data['transactions'][$key]['sheba'] = $get_name->sheba_acc;

            if($trans['ecurrency'] != 'bitcoin')
            {
                $this->data['transactions'][$key]['amount'] = $this->truncate_number($this->data['transactions'][$key]['amount']);
                $this->data['transactions'][$key]['unit']   = 'دلار';
            }
            else
            {
                $this->data['transactions'][$key]['unit']   = 'بیتکوین';
            }
        }

        $this->form_validation->set_rules('admin_note', 'توضیحات', 'required');

        if (isset($_POST) && !empty($_POST))
        {
            if ($this->form_validation->run() === TRUE)
            {
                $admin_note = htmlspecialchars($_POST['admin_note']);
                $data = array(
                    'admin_note'   => $admin_note,
                    'completed'    => 1
                );
                $this->db->where('id', $exchange_id);
                $update = $this->db->update('app_exchanges', $data);

                if($update)
                {
                    $message = 'مدیریت به صورت دستی اکسچنج شما را پرداخت کرد، شماره پیگیری در سایت: ';
                    $message .= $this->data['transactions'][0]['transaction_id'];

                    $this->db->select('*');
                    $this->db->where('id', $user_id);
                    $ecc = $this->db->get('app_users')->row();

                    $user_email = $ecc->email;
                    $mobile = $ecc->mobile;

                    $webpurse_email = $this->config->item('webpurse_email');
                    mail("{$user_email},{$webpurse_email}", 'WebPurse - Exchange Completed', $message, 'From: info@webpurse.org');

                    // Send Message
                    $this->load->model('our_model');
                    $this->our_model->send_sms_money_sent($mobile);

                    $this->session->set_flashdata('message', 'با موفقیت ذخیره شد');
                }
                else
                {
                    $this->session->set_flashdata('message', 'خطا در تغییر');
                }
                redirect("auth/exchanges", 'refresh');
            }
        }

        $this->data['admin_note'] = array(
            'name'  => 'admin_note',
            'id'    => 'admin_note',
            'value' => ''
        );

        $this->_render_page('auth/edit_exchange', $this->data);
    }


    function admin_settings()
    {
        if ( ! $this->ion_auth->is_admin() OR intval($this->ion_auth->get_user_id()) !== 1)
        {
            return show_error('صفحه مورد نظر یافت نشد');
            die();
        }

        if($this->_require_second_password() !== TRUE)
        {
            return FALSE;
        }

        $this->db->select('*');
        $this->db->where('id', 1);
        $settings = $this->db->get('app_settings')->row();

        // validate form input
        $this->form_validation->set_rules('require_pin_to_buy', 'نیاز به پین برای خرید', 'required|integer|less_than_equal_to[1]|greater_than_equal_to[0]');
        $this->form_validation->set_rules('sms_after_money_sent', 'ارسال پیغام پس از پرداخت اتوماتیک', 'required|integer|less_than_equal_to[1]|greater_than_equal_to[0]');
        $this->form_validation->set_rules('sms_number', 'شماره ارسال پیامک', 'required|numeric');
        $this->form_validation->set_rules('important_news', 'خبر مهم', 'integer');

        if (isset($_POST) && !empty($_POST) && isset($_POST['require_pin_to_buy']))
        {
            if ($this->form_validation->run() === TRUE)
            {
                $data = array(
                    'require_pin_to_buy'   => intval($_POST['require_pin_to_buy']),
                    'sms_after_money_sent' => intval($_POST['sms_after_money_sent']),
                    'sms_number'           => $_POST['sms_number'],
                    'important_news'       => intval($_POST['important_news']),
                );
                $this->db->where('id', 1);
                $settings_update = $this->db->update('app_settings', $data);

                if($settings_update)
                {
                    $this->session->set_flashdata('message', 'با موفقیت ذخیره شد');
                }
                else
                {
                    $this->session->set_flashdata('message', 'خطا در تغییر');
                }
                redirect("auth/admin_settings", 'refresh');
            }
        }

        // set the flash data error message if there is one
        $this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));

        // pass the user to the view
        $this->data['settings'] = $settings;

        $this->data['require_pin_to_buy'] = array(
            'name'  => 'require_pin_to_buy',
            'id'    => 'require_pin_to_buy',
            'value' => $this->form_validation->set_value('require_pin_to_buy', $settings->require_pin_to_buy),
        );

        $this->data['sms_after_money_sent'] = array(
            'name'  => 'sms_after_money_sent',
            'id'    => 'sms_after_money_sent',
            'value' => $this->form_validation->set_value('sms_after_money_sent', $settings->sms_after_money_sent),
        );

        $this->data['sms_number'] = array(
            'name'  => 'sms_number',
            'id'    => 'sms_number',
            'type'  => 'text',
            'value' => $this->form_validation->set_value('sms_number', $settings->sms_number),
        );

        $this->data['important_news'] = array(
            'name'  => 'important_news',
            'id'    => 'important_news',
            'type'  => 'text',
            'value' => $this->form_validation->set_value('important_news', $settings->important_news),
        );

        $this->_render_page('auth/admin_settings', $this->data);
    }

    function admin_accounts()
    {
        if ( ! $this->ion_auth->is_admin() OR intval($this->ion_auth->get_user_id()) !== 1)
        {
            return show_error('صفحه مورد نظر یافت نشد');
            die();
        }

        if($this->_require_second_password() !== TRUE)
        {
            return FALSE;
        }

        $this->db->select('*');
        $this->db->where('id', 1);
        $settings = $this->db->get('app_secret')->row();

        $this->load->library('encrypt');

        if (isset($_POST) && !empty($_POST) && isset($_POST['cat']) && $_POST['cat'] == 'foreign' && $_POST['ec'] == 'perfectmoney')
        {
            // validate form input
            $this->form_validation->set_rules('perfectmoney_account_id', 'شماره آی دی اکانت', 'trim|required|alpha_numeric');
            $this->form_validation->set_rules('perfectmoney_passphrase', 'رمز اکانت', 'trim|required');
            $this->form_validation->set_rules('perfectmoney_payer_account', 'حساب پردازنده', 'trim|required|alpha_numeric');

            if ($this->form_validation->run() === TRUE)
            {
                $data = array(
                    'perfectmoney_account_id'     => $_POST['perfectmoney_account_id'],
                    'perfectmoney_passphrase'     => base64_encode($this->encrypt->encode($_POST['perfectmoney_passphrase'], $this->config->item('en_usd'))),
                    'perfectmoney_payer_account'  => $_POST['perfectmoney_payer_account']
                );
                $this->db->where('id', 1);
                $settings_update = $this->db->update('app_secret', $data);

                if($settings_update)
                {
                    $this->session->set_flashdata('message', 'با موفقیت ذخیره شد');
                }
                else
                {
                    $this->session->set_flashdata('message', 'خطا در تغییر');
                }
                redirect("auth/admin_accounts", 'refresh');
            }
        }

        if (isset($_POST) && !empty($_POST) && isset($_POST['cat']) && $_POST['cat'] == 'foreign' && $_POST['ec'] == 'bitcoin_blockio')
        {
            // validate form input
            $this->form_validation->set_rules('bitcoin_blockio_api_key', 'ای پی آی کی بلاک یو', 'trim|required');
            $this->form_validation->set_rules('bitcoin_blockio_secret_pin', 'رمز مخفی بلاک یو', 'trim|required');
            $this->form_validation->set_rules('bitcoin_blockio_fee', 'مقدار فی', 'trim|required|alpha');

            if ($this->form_validation->run() === TRUE)
            {
                $data = array(
                    'bitcoin_blockio_api_key'     => $_POST['bitcoin_blockio_api_key'],
                    'bitcoin_blockio_secret_pin'  => base64_encode($this->encrypt->encode($_POST['bitcoin_blockio_secret_pin'], $this->config->item('en_usd'))),
                    'bitcoin_blockio_fee'         => $_POST['bitcoin_blockio_fee']
                );
                $this->db->where('id', 1);
                $settings_update = $this->db->update('app_secret', $data);

                if($settings_update)
                {
                    $this->session->set_flashdata('message', 'با موفقیت ذخیره شد');
                }
                else
                {
                    $this->session->set_flashdata('message', 'خطا در تغییر');
                }
                redirect("auth/admin_accounts", 'refresh');
            }
        }

        if (isset($_POST) && !empty($_POST) && isset($_POST['cat']) && $_POST['cat'] == 'foreign' && $_POST['ec'] == 'webmoney')
        {
            // validate form input
            $this->form_validation->set_rules('webmoney_wmid', 'Webmoney WMID', 'trim|required|alpha_numeric');
            $this->form_validation->set_rules('webmoney_sender_purse', 'Webmoney Sender Purse', 'trim|required|alpha_numeric');
            $this->form_validation->set_rules('webmoney_key_file_name', 'Webmoney Key File Name', 'trim|htmlspecialchars|required');
            $this->form_validation->set_rules('webmoney_password', 'Webmoney Password', 'trim|strip_tags|required');
            $this->form_validation->set_rules('webmoney_certificate', 'Webmoney Certificate', 'trim|strip_tags');

            if ($this->form_validation->run() === TRUE)
            {
                $data = array(
                    'webmoney_wmid'         => $_POST['webmoney_wmid'],
                    'webmoney_sender_purse' => $_POST['webmoney_sender_purse'],
                    'webmoney_key_file_name' => $_POST['webmoney_key_file_name'],
                    'webmoney_password'     => base64_encode($this->encrypt->encode($_POST['webmoney_password'], $this->config->item('en_usd'))),
                    'webmoney_certificate'  => $_POST['webmoney_certificate']
                );
                $this->db->where('id', 1);
                $settings_update = $this->db->update('app_secret', $data);

                if($settings_update)
                {
                    $this->session->set_flashdata('message', 'با موفقیت ذخیره شد');
                }
                else
                {
                    $this->session->set_flashdata('message', 'خطا در تغییر');
                }
                redirect("auth/admin_accounts", 'refresh');
            }
        }

        if (isset($_POST) && !empty($_POST) && isset($_POST['cat']) && $_POST['cat'] == 'iran')
        {
            // validate form input
            $this->form_validation->set_rules('bank_gateway_use', 'کدام درگاه', 'required|trim|in_list[mellat,pasargad]');

            $this->form_validation->set_rules('bank_pasargad_merchant_code', 'کد پذیرنده', 'trim|htmlspecialchars');
            $this->form_validation->set_rules('bank_pasargad_terminal_code', 'کد ترمینال', 'trim|htmlspecialchars');

            $this->form_validation->set_rules('bank_mellat_terminal_id', 'ترمینال کد بانک ملت', 'trim|htmlspecialchars');
            $this->form_validation->set_rules('bank_mellat_username', 'نام کاربری بانک ملت', 'trim|htmlspecialchars');
            $this->form_validation->set_rules('bank_mellat_password', 'کلمه عبور درگاه ملت', 'trim|htmlspecialchars');

            if ($this->form_validation->run() === TRUE)
            {
                $data = array(
                    'bank_gateway_use'  => $this->input->post('bank_gateway_use'),
                    'bank_pasargad_merchant_code'  => $this->input->post('bank_pasargad_merchant_code'),
                    'bank_pasargad_terminal_code'  => $this->input->post('bank_pasargad_terminal_code'),
                    'bank_mellat_terminal_id'  => $this->input->post('bank_mellat_terminal_id'),
                    'bank_mellat_username'  => $this->input->post('bank_mellat_username'),
                    'bank_mellat_password'  => base64_encode($this->encrypt->encode($this->input->post('bank_mellat_password'))),
                );
                $this->db->where('id', 1);
                $settings_update = $this->db->update('app_secret', $data);

                if($settings_update)
                {
                    $this->session->set_flashdata('message', 'با موفقیت ذخیره شد');
                }
                else
                {
                    $this->session->set_flashdata('message', 'خطا در تغییر');
                }
                redirect("auth/admin_accounts", 'refresh');
            }
        }

        // set the flash data error message if there is one
        $this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));

        // pass the user to the view
        $this->data['settings'] = $settings;

        $this->data['gateway'] = $settings->bank_gateway_use;

        $this->data['perfectmoney_account_id'] = array(
            'name'  => 'perfectmoney_account_id',
            'id'    => 'perfectmoney_account_id',
            'autocomplete' => 'off',
            'type'  => 'text',
            'value' => $this->form_validation->set_value('perfectmoney_account_id', $settings->perfectmoney_account_id),
        );

        $this->data['perfectmoney_passphrase'] = array(
            'name'  => 'perfectmoney_passphrase',
            'id'    => 'perfectmoney_passphrase',
            'autocomplete' => 'off',
            'type'  => 'password',
            'value' => $this->form_validation->set_value('perfectmoney_passphrase', $this->encrypt->decode(base64_decode($settings->perfectmoney_passphrase), $this->config->item('en_usd'))),
        );

        $this->data['perfectmoney_payer_account'] = array(
            'name'  => 'perfectmoney_payer_account',
            'id'    => 'perfectmoney_payer_account',
            'autocomplete' => 'off',
            'type'  => 'text',
            'value' => $this->form_validation->set_value('perfectmoney_payer_account', $settings->perfectmoney_payer_account),
        );

        $this->data['bitcoin_blockio_api_key'] = array(
            'name'  => 'bitcoin_blockio_api_key',
            'id'    => 'bitcoin_blockio_api_key',
            'autocomplete' => 'off',
            'type'  => 'text',
            'value' => $this->form_validation->set_value('bitcoin_blockio_api_key', $settings->bitcoin_blockio_api_key),
        );

        $this->data['bitcoin_blockio_secret_pin'] = array(
            'name'  => 'bitcoin_blockio_secret_pin',
            'id'    => 'bitcoin_blockio_secret_pin',
            'autocomplete' => 'off',
            'type'  => 'password',
            'value' => $this->form_validation->set_value('bitcoin_blockio_secret_pin', $this->encrypt->decode(base64_decode($settings->bitcoin_blockio_secret_pin), $this->config->item('en_usd'))),
        );

        $this->data['bitcoin_blockio_fee'] = array(
            'name'  => 'bitcoin_blockio_fee',
            'id'    => 'bitcoin_blockio_fee',
            'value' => $this->form_validation->set_value('bitcoin_blockio_fee', $settings->bitcoin_blockio_fee),
        );

        $this->data['webmoney_wmid'] = array(
            'name'  => 'webmoney_wmid',
            'id'    => 'webmoney_wmid',
            'autocomplete' => 'off',
            'type'  => 'text',
            'value' => $this->form_validation->set_value('webmoney_wmid', $settings->webmoney_wmid),
        );

        $this->data['webmoney_sender_purse'] = array(
            'name'  => 'webmoney_sender_purse',
            'id'    => 'webmoney_sender_purse',
            'value' => $this->form_validation->set_value('webmoney_sender_purse', $settings->webmoney_sender_purse),
        );

        $this->data['webmoney_key_file_name'] = array(
            'name'  => 'webmoney_key_file_name',
            'id'    => 'webmoney_key_file_name',
            'value' => $this->form_validation->set_value('webmoney_key_file_name', $settings->webmoney_key_file_name),
        );

        $this->data['webmoney_password'] = array(
            'name'  => 'webmoney_password',
            'id'    => 'webmoney_password',
            'autocomplete' => 'off',
            'type'  => 'password',
            'value' => $this->form_validation->set_value('webmoney_password', $this->encrypt->decode(base64_decode($settings->webmoney_password), $this->config->item('en_usd'))),
        );

        $this->data['webmoney_certificate'] = array(
            'name'  => 'webmoney_certificate',
            'id'    => 'webmoney_certificate',
            'autocomplete' => 'off',
            'type'  => 'textarea',
            'value' => $this->form_validation->set_value('webmoney_certificate', $settings->webmoney_certificate),
        );

        $this->data['bank_pasargad_merchant_code'] = array(
            'name'  => 'bank_pasargad_merchant_code',
            'id'    => 'bank_pasargad_merchant_code',
            'autocomplete' => 'off',
            'type'  => 'text',
            'value' => $this->form_validation->set_value('bank_pasargad_merchant_code', $settings->bank_pasargad_merchant_code),
        );

        $this->data['bank_pasargad_terminal_code'] = array(
            'name'  => 'bank_pasargad_terminal_code',
            'id'    => 'bank_pasargad_terminal_code',
            'autocomplete' => 'off',
            'type'  => 'text',
            'value' => $this->form_validation->set_value('bank_pasargad_terminal_code', $settings->bank_pasargad_terminal_code),
        );

        $this->data['bank_mellat_terminal_id'] = array(
            'name'  => 'bank_mellat_terminal_id',
            'id'    => 'bank_mellat_terminal_id',
            'autocomplete' => 'off',
            'type'  => 'text',
            'value' => $this->form_validation->set_value('bank_mellat_terminal_id', $settings->bank_mellat_terminal_id),
        );

        $this->data['bank_mellat_username'] = array(
            'name'  => 'bank_mellat_username',
            'id'    => 'bank_mellat_username',
            'autocomplete' => 'off',
            'type'  => 'text',
            'value' => $this->form_validation->set_value('bank_mellat_username', $settings->bank_mellat_username),
        );

        $this->data['bank_mellat_password'] = array(
            'name'  => 'bank_mellat_password',
            'id'    => 'bank_mellat_password',
            'autocomplete' => 'off',
            'type'  => 'text',
            'value' => $this->form_validation->set_value('bank_mellat_password', $this->encrypt->decode(base64_decode($settings->bank_mellat_password))),
        );

        $this->_render_page('auth/admin_accounts', $this->data);
    }





















    // redirect if needed, otherwise display the user list
    function account()
    {
        $this->data['user'] = $this->ion_auth->user()->row();

        $this->_render_page('auth/users/account', $this->data);
    }


    // If not verified show this if clicking account links (except verify)
    function need_verify()
    {
        $this->_render_page('auth/users/need_verify', $this->data);
    }


    // Verify
    function verify()
    {
        // Load Model
        $this->load->model('our_model');
        $this->data['did_upload'] = FALSE;

        if (isset($_SESSION['did_upload']) && $_SESSION['did_upload'] == '1')
        {
            $this->data['did_upload'] = TRUE;
        }

        $user_id = $this->ion_auth->user()->row()->id;

        if ($this->input->post('step') === 'verify_mobile_code')
        {
            $verify_code = $this->input->post('verify_code',TRUE);
            $check_code  = $this->our_model->check_verify_code($user_id, $verify_code);
        }

        if ($this->input->post('step') === 'mobile')
        {
            $mobile = $this->input->post('mobile', TRUE);

            if ( ! is_numeric($mobile) OR strlen($mobile) != 11 OR substr($mobile,0,2) != '09')
            {
                $this->session->set_flashdata('message', 'شماره موبایل وارد شده معتبر نیست');
            }
            else
            {
                // Update it in database
                $data = array(
                    "mobile"     => $mobile
                );
                $this->db->where('id', $user_id);
                $this->db->update('app_users', $data);

                // Send Code
                $send_code = $this->our_model->send_verify_code($user_id, $mobile);
                if ($send_code !== FALSE)
                {
                    $this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');
                    $this->_render_page('auth/users/verify_mobile_code', $this->data);
                    return TRUE;
                }
            }
        }


        if ($this->input->post('step') === 'melli')
        {
            $config['upload_path']          = './program/verify_scans/';
            $config['allowed_types']        = 'gif|jpg|png|jpeg';
            $config['max_size']             = 3000;
            $config['max_width']            = 8000;
            $config['max_height']           = 8000;
            $config['file_name']            = 'document_userid_'.$user_id;
            $config['overwrite']            = TRUE;
            $config['file_ext_tolower']     = TRUE;

            $this->load->library('upload', $config);

            if ( ! $this->upload->do_upload('scan'))
            {
                $error = $this->upload->display_errors();
                $this->session->set_flashdata('message', "{$error}");
            }
            else
            {
                $_SESSION['did_upload'] = '1';
                $this->data['did_upload'] = TRUE;
            }
        }

        $this->data['user'] = $this->ion_auth->user()->row();
        $this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');
        $this->_render_page('auth/users/verify', $this->data);
    }


    // Exchange
    function exchange()
    {
        $this->data['user']       = $this->ion_auth->user()->row();
        $this->data['currencies'] = $this->ion_auth->user()->row();
        $this->data['message']    = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');

        $ecurrencies = $this->db->get('app_prices')->result_array();

        foreach($ecurrencies as $key => $value)
        {
            $name = $value['english_name'];
            $this->data['active'][$name] = intval($value['active']);
            $this->data['sell_price'][$name] = intval($value['sell_price']);
            $this->data['buy_price'][$name] = intval($value['buy_price']);
            $this->data['available'][$name] = $value['available'];
            if($value['english_name']!='bitcoin'){$this->data['available'][$name] = $this->truncate_number($value['available']);}
        }

        $this->_render_page('auth/users/exchange', $this->data);
    }


    // Buy
    function buy()
    {
        // Get segment
        $currency = html_escape($this->uri->segment(3));

        // Only Iran
        // Baraye gheyre faal kardan, kafie ghable die( bezanid //
        if(isset($_SERVER["HTTP_CF_IPCOUNTRY"]) && $_SERVER["HTTP_CF_IPCOUNTRY"] != 'IR')
        {
            die('ورود فقط با آی پی ایران ممکن است، در صورتی که فکر می کنید آی پی شما ایران است با مدیریت تماس بگیرید');
        }

        if ($this->uri->segment(3) === FALSE OR ($currency != 'perfectmoney' && $currency != 'bitcoin' && $currency != 'okpay' && $currency != 'paypal' && $currency != 'webmoney' && $currency != 'skrill' && $currency != 'btce'))
        {
            $this->session->set_flashdata('message', 'نوع اکانت معتبر نیست');
            redirect('auth/exchange', 'refresh');
        }

        $this->db->select('*');
        $this->db->where('english_name', $currency);
        $curr_info = $this->db->get('app_prices')->row();

        if (intval($curr_info->temp_disable) === 1)
        {
            $this->session->set_flashdata('message', 'در حال حاضر به دلیل عدم اتصال به سرور این ارز موقتا غیر فعال است لطفاطی ساعات آینده تلاش بفرمایید');
            redirect('auth/exchange', 'refresh');
        }

        $this->data['price']        = $this->data['buy_price'] = $curr_info->buy_price;
        $this->data['english_name'] = $curr_info->english_name;
        $this->data['persian_name'] = $curr_info->persian_name;
        $this->data['min_amount']   = $curr_info->buy_min_amount;
        $this->data['max_amount']   = $curr_info->buy_max_amount;
        $this->data['description']  = $curr_info->description;
        $this->data['unit']         = 'بیتکوین';

        $user_id = $this->ion_auth->user()->row()->id;
        $full_acc = $currency . '_acc';
        $this->db->select($full_acc);
        $this->db->where('id', $user_id);
        $your_accounts = $this->db->get('app_users')->row();
        $this->data['pay_to_account']  = $your_accounts->$full_acc;

        // Max amount is daily, check and set users limit based on what he used
        $this->data['has_another_exchange'] = FALSE;
        $sum_today = $this->db->query("SELECT IFNULL(SUM(amount),0) AS total FROM app_exchanges WHERE user_id = {$user_id} AND ecurrency = '{$currency}' AND buy_or_sell = 'buy' AND DATE(date) = CURDATE()")->row_array();
        if ( ! $sum_today OR ! isset($sum_today['total']) OR ! is_numeric($sum_today['total']))
        {
            echo "Error #783<br>";
            die('خطایی رخ داد لطفا بعدا امحان کنید یا با مدیریت تماس بگیرید');
        }
        $today_used = $sum_today['total'];
        // New Max
        if ($today_used > 0)
        {
            $this->data['has_another_exchange'] = TRUE;
            $this->data['max_amount'] = bcsub("{$this->data['max_amount']}","{$today_used}",8);
            if ($curr_info->english_name != 'bitcoin')
            {
                $this->data['max_amount'] = $this->truncate_number($this->data['max_amount'], 2);
            }
        }
        if ($this->data['max_amount'] < 0)
        {
            $this->data['max_amount'] = 0;
        }

        if ($curr_info->available < $this->data['max_amount'])
        {
            $this->data['max_amount'] = $curr_info->available;
        }

        if ($curr_info->english_name != 'bitcoin')
        {
            $this->data['unit']       = 'دلار';
            $this->data['min_amount'] = $this->truncate_number($this->data['min_amount'], 2);
            $this->data['max_amount'] = $this->truncate_number($this->data['max_amount'], 2);
        }

        // Need SMS Verify
        if($this->_require_sms_verification($user_id) !== TRUE)
        {
            return FALSE;
        }

        $this->data['havent_set_account'] = FALSE;
        if (strlen($this->data['pay_to_account']) <= 1)
        {
            $this->data['pay_to_account'] = 'شماره حساب خود را ذخیره نکرده اید!';
            $this->data['havent_set_account'] = TRUE;
        }

        $this->data['extra_info'] = array(
            'name'         => 'extra_info',
            'id'           => 'extra_info',
            'value'        => ''
        );

        $this->form_validation->set_rules('amount', 'مبلغ', "required|numeric|greater_than_equal_to[{$this->data['min_amount']}]|less_than_equal_to[{$this->data['max_amount']}]");

        if (isset($_POST) && !empty($_POST) && isset($_POST['amount']))
        {
            if ($this->form_validation->run() === TRUE)
            {
                $date       = date('Y-m-d H:i:s');
                $ip_address = $this->input->ip_address();
                $amount     = $this->input->post('amount');
                $extra_info = html_escape($this->input->post('extra_info', TRUE));
                $transaction_id = date('YmdHis') . "{$user_id}" . rand(1,9);

                $wm_code = intval($this->session->userdata('wm_code'));

                if (strlen($wm_code) >= 3 && $wm_code > 100 && intval($this->input->post('wm_protect')) === 1)
                {
                    $wm_protect = 1;
                }
                else
                {
                    $wm_protect = 0;
                    $wm_code = 0;
                }

                // Check if amount more or less
                if ($amount > $this->data['max_amount'] OR $amount < $this->data['min_amount'] OR $amount > $curr_info->available)
                {
                    $this->session->set_flashdata('message', 'مبلغ وارد شده کمتر یا بیشتر از مقدار معتبر یا موجودی سایت است');
                    redirect('auth/buy', 'refresh');
                }

                $rials = bcmul("{$this->data['price']}","$amount",0);

                $data = array(
                    'buy_or_sell' => 'buy',
                    'date' => $date,
                    'amount' => $amount,
                    'rials' => $rials,
                    'user_id' => $user_id,
                    'ecurrency' => $this->data['english_name'],
                    'ip_address' => $ip_address,
                    'extra_info' => $extra_info,
                    'transaction_id' => $transaction_id,
                    'wm_protect' => $wm_protect,
                    'wm_code' => $wm_code
                );

                $insert = $this->db->insert('app_exchanges', $data);

                if ($insert)
                {
                    $this->load->library('encrypt');

                    // Get Bank
                    $this->db->select('*');
                    $this->db->where('id', 1);
                    $bank_info = $this->db->get('app_secret')->row();

                    $gateway = $bank_info->bank_gateway_use;

                    $callback_url = "https://webpurse.org/index.php/auth/return_bank_{$gateway}/{$currency}/{$transaction_id}";

                    $rials = bcmul("{$amount}","{$this->data['price']}",0);
                    $rials = bcmul("{$rials}",'10',0);

                    // Bank Mellat
                    if ($gateway == 'mellat')
                    {
                        // paystarTransactionId
                        //Removed to change with pay.ir api
                        //$this->load->library('nusoap_lib');
                        //$terminalId   = $bank_info->bank_mellat_terminal_id;
                        //$userName     = $bank_info->bank_mellat_username;
                        //$userPassword = $this->encrypt->decode(base64_decode("{$bank_info->bank_mellat_password}"));
                        // $pay_api = $this->encrypt->decode(base64_decode("{$bank_info->bank_mellat_password}"));
                        $pay_amount = $rials;
                        $pay_redirect = $callback_url;
                        $pay_factorNumber = $transaction_id;
                        $pay_description = "{$user_id} کاربر شماره";

                        // $payir = $this->curl_post('https://pay.ir/pg/send', [
                        // 	'api'          => $pay_api,
                        // 	'amount'       => $pay_amount,
                        // 	'redirect'     => $pay_redirect,
                        // 	'factorNumber' => $pay_factorNumber,
                        // 	'description'  => $pay_description,
                        // ]);

                        // =================== Paystar =============================

                        // $pin = 'AB2CC261EE0C2A284642';
                        // $amount = $pay_amount/10;
                        // $callback = $pay_redirect;
                        // $description = $pay_description;
                        // $url = 'https://paystar.ir/api/create';
                        //   if (getenv('HTTP_CLIENT_IP'))
                        //         $ipaddress = getenv('HTTP_CLIENT_IP');
                        //     else if(getenv('HTTP_X_FORWARDED_FOR'))
                        //         $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
                        //     else if(getenv('HTTP_X_FORWARDED'))
                        //         $ipaddress = getenv('HTTP_X_FORWARDED');
                        //     else if(getenv('HTTP_FORWARDED_FOR'))
                        //         $ipaddress = getenv('HTTP_FORWARDED_FOR');
                        //     else if(getenv('HTTP_FORWARDED'))
                        //       $ipaddress = getenv('HTTP_FORWARDED');
                        //     else if(getenv('REMOTE_ADDR'))
                        //         $ipaddress = getenv('REMOTE_ADDR');
                        //     else
                        //         $ipaddress = 'UNKNOWN';

                        // $fields = [
                        //     'pin'          => $pin,
                        //     'amount'       => $amount,
                        //     'callback'     => $callback,
                        //     'description'  => $description,
                        //     'ip'            => $ipaddress
                        // ];
                        // $ch = curl_init();
                        // curl_setopt($ch,CURLOPT_URL, $url);
                        // curl_setopt($ch,CURLOPT_POST, count($fields));
                        // curl_setopt($ch,CURLOPT_POSTFIELDS, $fields);
                        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        // $transID = curl_exec($ch);
                        // curl_close($ch);


                        //     if(!is_numeric($transID)){

                        //         $go = "https://paystar.ir/paying/".$transID;
                        //         $paystarTransactionId = $transID;
                        //         header("Location: $go");
                        //         exit('چند لحظه صبر کنید');
                        //       } else {

                        //         $this->data['has_error'] = TRUE;
                        //         $this->data['message'] = "خطای : {$transID}";
                        //       }


                        // ========================== End Paystar =========================

                        // ======================  ZarrinPal =============================



                        $price =  $pay_amount/10;
                        $MerchantID = '8346362e-48e4-11e9-9901-000c295eb8fc'; //Required
                        $verifyUrl = $callback = $pay_redirect;
                        $data = array('MerchantID' => $MerchantID,
                            'Amount' => $price,
                            'CallbackURL' => $verifyUrl,
                            'Description' => 'فروشگاه محصولات دیجیتال');
                        $jsonData = json_encode($data);
                        $ch = curl_init('https://www.zarinpal.com/pg/rest/WebGate/PaymentRequest.json');
                        curl_setopt($ch, CURLOPT_USERAGENT, 'ZarinPal Rest Api v1');
                        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                            'Content-Type: application/json',
                            'Content-Length: ' . strlen($jsonData)
                        ));
                        $result = curl_exec($ch);
                        $err = curl_error($ch);
                        $result = json_decode($result, true);
                        curl_close($ch);
                        if ($err) {
                            echo "cURL Error #:" . $err;
                        } else {
                            if ($result["Status"] == '100' ) {
                                header('Location: https://www.zarinpal.com/pg/StartPay/' . $result["Authority"]);
                            } else {
                                echo'ERR: ' . $result["Status"];
                            }
                        }



                        //  ======================  End ZarrinPal =============================

                        // $pay_result = json_decode($payir);

                        // if (isset($pay_result) && isset($pay_result->token) && $pay_result->status)
                        // {
                        // 	$go = "https://pay.ir/pg/$pay_result->token";
                        // 	header("Location: $go");
                        // 	exit('چند لحظه صبر کنید');
                        // }
                        // elseif(isset($pay_result->errorMessage))
                        // {
                        // 	$this->data['has_error'] = TRUE;
                        // 	$this->data['message'] = "خطای ۲۴۶: {$pay_result->errorMessage}";
                        // }
                        // else
                        // {
                        // 	$this->data['has_error'] = TRUE;
                        // 	$this->data['message'] = "خطای ۲۴۷";
                        // }

                        $this->_render_page('auth/users/redirect_to_bank_mellat', $this->data);
                        return TRUE;
                    }
                    // Bank Pasargad
                    else
                    {
                        $this->load->library('rsaprocessor');

                        $processor = new RSAProcessor();
                        $this->data['merchantCode'] = $bank_info->bank_pasargad_merchant_code;
                        $this->data['terminalCode'] = $bank_info->bank_pasargad_terminal_code;
                        $this->data['amount'] = $rials;
                        $this->data['redirectAddress'] = $callback_url;
                        $this->data['invoiceNumber'] = $transaction_id;
                        $this->data['timeStamp'] = date("Y/m/d H:i:s");
                        $this->data['invoiceDate'] = date("Y/m/d H:i:s");
                        $this->data['action'] = '1003';
                        $this->data['data'] = "#". $this->data['merchantCode'] ."#". $this->data['terminalCode'] ."#". $this->data['invoiceNumber'] ."#". $this->data['invoiceDate'] ."#". $this->data['amount'] ."#". $this->data['redirectAddress'] ."#". $this->data['action'] ."#". $this->data['timeStamp'] ."#";
                        $this->data['data'] = sha1($this->data['data'],true);
                        $this->data['data'] = $processor->sign($this->data['data']);
                        $this->data['result'] = base64_encode($this->data['data']);

                        $this->_render_page('auth/users/redirect_to_bank_pasargad', $this->data);
                        return TRUE;
                    }
                }
                else
                {

                }
            }
        }

        if ($curr_info->english_name == 'webmoney')
        {
            $this->data['wm_code'] = rand(1000000,9999999);
            $this->session->set_userdata('wm_code', "{$this->data['wm_code']}");
            \Carbon\Carbon::parse('Your Time')->addMinutes(30);
        }
        else
        {
            $this->data['wm_code'] = 0;
            $this->session->set_userdata('wm_code', '0');
        }

        $this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));
        $this->_render_page('auth/users/buy', $this->data);
    }



    function return_bank_pasargad()
    {
        $currency = $this->uri->segment(3);
        $exchange_transaction_id = htmlspecialchars($this->uri->segment(4),ENT_QUOTES,'UTF-8');

        if ($this->uri->segment(3) === FALSE OR ($currency != 'perfectmoney' && $currency != 'bitcoin' && $currency != 'okpay' && $currency != 'paypal' && $currency != 'webmoney' && $currency != 'skrill' && $currency != 'btce'))
        {
            $this->session->set_flashdata('message', 'خطایی رخ داده و نوع اکانت معتبر نیست لطفا در صورت واریز وجه با مدیریت تماس بگیرید');
            $this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));
            $this->_render_page('auth/users/return_bank_pasargad', $this->data);
            return FALSE;
        }

        if ( ! isset($_GET['tref']))
        {
            $this->session->set_flashdata('message', 'بانک اطلاعات را به درستی باز نگرداند، در صورتی که مبلغ از حساب شما کسر شده، با مدیریت تماس بگیرید');
            $this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));
            $this->_render_page('auth/users/return_bank_pasargad', $this->data);
            return FALSE;
        }

        // Check if this is already completed
        $this->db->select('*');
        $this->db->where('transaction_id', $exchange_transaction_id);
        $exchange = $this->db->get('app_exchanges')->row();

        // Invalid
        if ( ! $exchange OR $exchange_transaction_id != $_GET['iN'])
        {
            $this->session->set_flashdata('message', 'کد تراکنش نامعتبر است');
            $this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));
            $this->_render_page('auth/users/return_bank_pasargad', $this->data);
            return FALSE;
        }

        // Already Paid
        if ($exchange->completed > 0)
        {
            $this->session->set_flashdata('message', 'این تراکنش قبلا پرداخت شده');
            $this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));
            $this->_render_page('auth/users/return_bank_pasargad', $this->data);
            return FALSE;
        }

        $transaction_id = htmlspecialchars($this->uri->segment(4),ENT_QUOTES,'UTF-8');

        $fields = array('invoiceUID' => $_GET['tref']);
        $result = $this->post2https($fields,'https://pep.shaparak.ir/CheckTransactionResult.aspx');

        $bank_result = $this->makeXMLTree($result);

        unset($fields);

        if ( ! isset($bank_result['invoiceDate']))
        {
            $this->session->set_flashdata('message', 'خطا در اتصال با بانک جهت تایید واریز، در صورت کسر مبلغ از طرف بانک اتوماتیک به حساب شما بازگردانده می شود');
            $this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));
            $this->_render_page('auth/users/return_bank_pasargad', $this->data);
            return FALSE;
        }


        // Verify Payment
        // Get Bank
        $this->db->select('*');
        $this->db->where('id', 1);
        $bank_info = $this->db->get('app_secret')->row();
        // This doesnt work
        $this->load->library('rsaprocessor');

        // Delete these
        if ( !isset($bank_result['invoiceNumber'])){$bank_result['invoiceNumber'] = '1';}
        if ( !isset($bank_result['invoiceDate'])){$bank_result['invoiceDate'] = '2';}
        if ( !isset($bank_result['amount'])){$bank_result['amount'] = '3';}

        $fields = array(
            'MerchantCode' => $bank_info->bank_pasargad_merchant_code,
            'TerminalCode' => $bank_info->bank_pasargad_terminal_code,
            'InvoiceNumber' => $bank_result['invoiceNumber'],
            'InvoiceDate' => $bank_result['invoiceDate'],
            'amount' => $bank_result['amount'],
            'TimeStamp' => date("Y/m/d H:i:s"),
            'sign' => ''
        );

        $bank_transaction_reference_id = htmlspecialchars($_GET['tref'],ENT_QUOTES,'UTF-8');
        $bank_invoice_id               = $bank_result['invoiceNumber'];
        $bank_invoice_date             = $bank_result['invoiceDate'];

        $processor = new RSAProcessor();
        $data = "#". $fields['MerchantCode'] ."#". $fields['TerminalCode'] ."#". $fields['InvoiceNumber'] ."#". $fields['InvoiceDate'] ."#". $fields['amount'] ."#". $fields['TimeStamp'] ."#";
        $data = sha1($data,true);
        $data =  $processor->sign($data);
        $fields['sign'] =  base64_encode($data);

        $verifyresult = $this->post2https($fields,'https://pep.shaparak.ir/VerifyPayment.aspx');
        $bank_verify = $this->makeXMLTree($verifyresult);

        // Fail
        if( ! isset($bank_verify['result']) OR ($bank_verify['result'] != 'True' && $bank_verify['result'] != 'true') OR ! isset($bank_result['result']) OR ($bank_result['result'] != 'True' && $bank_result['result'] != 'true') OR ! isset($bank_result['invoiceNumber']) OR $transaction_id != $bank_result['invoiceNumber'])
        {
            $this->session->set_flashdata('message', 'خطایی رخ داد لطفا در صورت واریز وجه با مدیریت تماس بگیرید');
        }
        // Success
        else
        {
            // Update Bank Info
            $data = array(
                "bank_transaction_reference_id" => $bank_transaction_reference_id,
                "bank_invoice_id"               => $bank_invoice_id,
                "bank_invoice_date"             => $bank_invoice_date
            );
            $this->db->where('transaction_id', $transaction_id);
            $this->db->update('app_exchanges', $data);

            // Get Info to check
            $this->db->select('*');
            $this->db->where('transaction_id', $bank_result['invoiceNumber']);
            $transaction = $this->db->get('app_exchanges')->row();
            $send_amount = $transaction->amount;

            if ( ! $transaction OR $transaction->ecurrency != $currency)
            {
                $this->session->set_flashdata('message', '2خطایی رخ داده و نوع اکانت معتبر نیست لطفا در صورت واریز وجه با مدیریت تماس بگیرید');
            }
            else
            {
                // Do things

                // Try to insert into Instant Pay table (Unique) and check
                $this->load->model('our_model');
                $instapay = $this->our_model->is_system_allowed_to_send_money($transaction_id, $transaction->user_id);

                // Load Encryption
                $this->load->library('encrypt');

                if ($currency == 'bitcoin' && $instapay > 0)
                {
                    $this->our_model->pay_bitcoin($transaction->user_id, $transaction_id);
                }

                elseif ($currency == 'perfectmoney' && $instapay > 0)
                {
                    $this->our_model->pay_perfectmoney($transaction->user_id, $transaction_id);
                }

                elseif ($currency == 'webmoney' && $instapay > 0)
                {
                    // Load Webmoney Model
                    $this->load->model('webmoney_model');
                    $this->webmoney_model->pay_webmoney($transaction->user_id, $transaction_id);
                }

                else
                {
                    $webpurse_email = $this->config->item('webpurse_email');
                    mail("$webpurse_email", "New Pending Exchange (Buy {$currency})", $transaction_id, 'From: info@webpurse.org');
                    $this->session->set_flashdata('message', 'مبلغ ریالی دریافت شد، مدیریت در اسرع وقت مبلغ را به حساب ارزی ذخیره شده شما واریز خواهد کرد');
                }

            }
        }

        $this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));
        $this->_render_page('auth/users/return_bank_pasargad', $this->data);
    }


    function return_bank_mellat(){

        // ====================== ZarrinPal Verify ======================
        $currency = $this->uri->segment(3);
        $exchange_transaction_id = htmlspecialchars($this->uri->segment(4));

        if ($this->uri->segment(3) === FALSE OR ($currency != 'perfectmoney' && $currency != 'bitcoin' && $currency != 'okpay' && $currency != 'paypal' && $currency != 'webmoney' && $currency != 'skrill' && $currency != 'btce'))
        {
            $this->session->set_flashdata('message', 'خطایی رخ داده و نوع اکانت معتبر نیست لطفا در صورت واریز وجه با مدیریت تماس بگیرید');
            $this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));
            $this->_render_page('auth/users/return_bank_mellat', $this->data);
            return FALSE;
        }

        // Load Encryption
        $this->load->library('encrypt');

        //$this->load->library('nusoap_lib');

        $transaction_id = htmlspecialchars($this->uri->segment(4));

        // Get Bank
        $this->db->select('*');
        $this->db->where('id', 1);
        $bank_info = $this->db->get('app_secret')->row();
        //$terminalId   = $bank_info->bank_mellat_terminal_id;
        //$userName     = $bank_info->bank_mellat_username;
        //$userPassword = $this->encrypt->decode(base64_decode($bank_info->bank_mellat_password));
        // $pay_api = $this->encrypt->decode(base64_decode($bank_info->bank_mellat_password));
        // $pay_token = $_GET['token'];
        // $pay_verify =  $this->curl_post('https://pay.ir/pg/verify', [
        // 	'api' 	=> $pay_api,
        // 	'token' => $pay_token,
        // ]);

        $this->db->select('*');
        $this->db->where('transaction_id', $exchange_transaction_id);
        $exchange = $this->db->get('app_exchanges')->row();

        $Authority = $_GET['Authority'];

        $data = array('MerchantID' => '8346362e-48e4-11e9-9901-000c295eb8fc', 'Authority' => $Authority, 'Amount'=>$exchange->rials);
        $jsonData = json_encode($data);
        $ch = curl_init('https://www.zarinpal.com/pg/rest/WebGate/PaymentVerification.json');
        curl_setopt($ch, CURLOPT_USERAGENT, 'ZarinPal Rest Api v1');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData)
        ));
        $result = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        $result = json_decode($result, true);
        if ($err) {
            return "cURL Error #:" . $err;
        } else {
            if ($result['Status'] == '100') {
                //echo 'Transation success. RefID:' . $result['RefID'];
                $pay_success = TRUE;
                // return [
                //     'body'=>'عملیات پرداخت با موفقیت انجام شد',
                //     'code' => '200'
                // ];


            } else {
// echo 'Transation failed. Status:' . $result['Status'];
//	return redirect()->route('credit',['username'=>$request->username])->with(['Error'=>'تراکنش موفقیت آمیز نبود']);
                $pay_success = FALSE;

            }
        }


            // $orderId = $pay_result->factorNumber;

            // Check if this is already completed
            $this->db->select('*');
            $this->db->where('transaction_id', $exchange_transaction_id);
            $exchange = $this->db->get('app_exchanges')->row();

            // Invalid
            // if ( ! $exchange OR $exchange_transaction_id != $pay_result->factorNumber)
            // {
            // 	$this->session->set_flashdata('message', 'کد تراکنش نامعتبر است');
            // 	$this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));
            // 	$this->_render_page('auth/users/return_bank_mellat', $this->data);
            // 	return FALSE;
            // }

            if ( ! $exchange OR $exchange_transaction_id != $exchange->transaction_id)
            {
                $this->session->set_flashdata('message', 'کد تراکنش نامعتبر است');
                $this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));
                $this->_render_page('auth/users/return_bank_mellat', $this->data);
                return FALSE;
            }

            // Already Paid
            if ($exchange->completed > 0)
            {
                $this->session->set_flashdata('message', 'این تراکنش قبلا پرداخت شده');
                $this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));
                $this->_render_page('auth/users/return_bank_mellat', $this->data);
                return FALSE;
            }

            // Success

            $bank_invoice_id               = preg_replace('/[^\w-]/', '', $exchange_transaction_id);
            $bank_transaction_reference_id = preg_replace('/[^\w-]/', '', $result['RefID']);
            // $bank_invoice_id               = preg_replace('/[^\w-]/', '', $pay_result->factorNumber);
            // Update Bank Info
            $data = array(
                "bank_transaction_reference_id" => $bank_transaction_reference_id,
                "bank_invoice_id"               => $bank_invoice_id
            );
            $this->db->where('transaction_id', $transaction_id);
            $this->db->update('app_exchanges', $data);

            // Get Info to check
            $this->db->select('*');
            // $this->db->where('transaction_id', $pay_result->factorNumber);
            $this->db->where('transaction_id', $transaction_id);
            $transaction = $this->db->get('app_exchanges')->row();
            $send_amount = $transaction->amount;

            // Check amount
            // $check_rials = bcmul("{$transaction->rials}",'10',0);
            // if ( ! $transaction OR ! is_numeric($transaction->rials) OR $pay_result->amount != $check_rials)
            // {
            // 	$this->data['message'] = 'سیستم در تایید مبلغ دریافتی ناموفق بود لطفا با مدیریت جهت تایید پرداخت تماس بگیرید';
            // 	$this->_render_page('auth/users/return_bank_mellat', $this->data);
            // 	return FALSE;
            // }

            if ( ! $transaction OR $transaction->ecurrency != $currency)
            {
                $this->session->set_flashdata('message', '2خطایی رخ داده و نوع اکانت معتبر نیست لطفا در صورت واریز وجه با مدیریت تماس بگیرید');
            }
            else
            {
                // Do things

                // Try to insert into Instant Pay table (Unique) and check
                $this->load->model('our_model');
                $instapay = $this->our_model->is_system_allowed_to_send_money($transaction_id, $transaction->user_id);

                // Load Encryption
                $this->load->library('encrypt');

                if ($currency == 'bitcoin' && $instapay > 0)
                {
                    $this->our_model->pay_bitcoin($transaction->user_id, $transaction_id);
                }

                elseif ($currency == 'perfectmoney' && $instapay > 0)
                {
                    $this->our_model->pay_perfectmoney($transaction->user_id, $transaction_id);
                }

                elseif ($currency == 'webmoney' && $instapay > 0)
                {
                    // Load Webmoney Model
                    $this->load->model('webmoney_model');
                    $this->webmoney_model->pay_webmoney($transaction->user_id, $transaction_id);
                }
                else
                {
                    $webpurse_email = $this->config->item('webpurse_email');
                    mail("$webpurse_email", "New Pending Exchange (Buy {$currency})", $transaction_id, 'From: info@webpurse.org');
                    $this->session->set_flashdata('message', 'مبلغ ریالی دریافت شد، مدیریت در اسرع وقت مبلغ را به حساب ارزی ذخیره شده شما واریز خواهد کرد');
                }

            }

            $this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));
            $this->_render_page('auth/users/return_bank_mellat', $this->data);


            // =====================  ZarrinPal End Verify =====================

        }


        function return_bank_mellat2()
        {
            $currency = $this->uri->segment(3);
            $exchange_transaction_id = htmlspecialchars($this->uri->segment(4));

            if ($this->uri->segment(3) === FALSE OR ($currency != 'perfectmoney' && $currency != 'bitcoin' && $currency != 'okpay' && $currency != 'paypal' && $currency != 'webmoney' && $currency != 'skrill' && $currency != 'btce'))
            {
                $this->session->set_flashdata('message', 'خطایی رخ داده و نوع اکانت معتبر نیست لطفا در صورت واریز وجه با مدیریت تماس بگیرید');
                $this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));
                $this->_render_page('auth/users/return_bank_mellat', $this->data);
                return FALSE;
            }

            // Load Encryption
            $this->load->library('encrypt');

            //$this->load->library('nusoap_lib');

            $transaction_id = htmlspecialchars($this->uri->segment(4));

            // Get Bank
            $this->db->select('*');
            $this->db->where('id', 1);
            $bank_info = $this->db->get('app_secret')->row();
            //$terminalId   = $bank_info->bank_mellat_terminal_id;
            //$userName     = $bank_info->bank_mellat_username;
            //$userPassword = $this->encrypt->decode(base64_decode($bank_info->bank_mellat_password));
            // $pay_api = $this->encrypt->decode(base64_decode($bank_info->bank_mellat_password));
            // $pay_token = $_GET['token'];
            // $pay_verify =  $this->curl_post('https://pay.ir/pg/verify', [
            // 	'api' 	=> $pay_api,
            // 	'token' => $pay_token,
            // ]);

            $this->db->select('*');
            $this->db->where('transaction_id', $exchange_transaction_id);
            $exchange = $this->db->get('app_exchanges')->row();

// ========================= Paystar Verify =======================================================

            $url = 'https://paystar.ir/api/verifycardnumber';
            $fields = [
                'pin' 	=> 'AB2CC261EE0C2A284642',
                'transid' => $_POST['transid'],
                'amount' => $exchange->rials,
            ];
            $ch = curl_init();
            curl_setopt($ch,CURLOPT_URL, $url);
            curl_setopt($ch,CURLOPT_POST, count($fields));
            curl_setopt($ch,CURLOPT_POSTFIELDS, $fields);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $pay_verify = curl_exec($ch);
            curl_close($ch);

            $pay_result = json_decode($pay_verify);

            if($pay_result->status == 1){

                $cardNumber = $pay_result->cardnumber ;

            } else {

                $this->data['has_error'] = TRUE;

                // $this->data['message'] = "خطا : تراکنش انجام نشد";
                $this->data['message'] =  $exchange->rials;
            }

            // ======================= End Paystart Verify ============================


            $pay_success = FALSE;

            // ============   Pay Docs =============
            // if(isset($pay_result->status) && isset($pay_result->amount) && isset($pay_result->factorNumber))
            // {
            // 	if($pay_result->status == 1 && is_numeric($pay_result->amount) && $pay_result->amount >= 1000)
            // 	{
            // 		$pay_success = TRUE;
            // 	} else {
            // 		$this->data['message'] = "خطای شماره ۲۶۲";
            // 	}
            // } else {
            // 	if($_GET['status'] == 0)
            // 	{
            // 		$this->data['message'] = "خطای شماره ۲۶۳";
            // 	}
            // }

            // =========================================

            if($pay_result->status == 1){

                $pay_success = TRUE;
            }

            if ( ! $pay_success)
            {
                $this->_render_page('auth/users/return_bank_mellat', $this->data);
                return FALSE;
            }

            // $orderId = $pay_result->factorNumber;

            // Check if this is already completed
            $this->db->select('*');
            $this->db->where('transaction_id', $exchange_transaction_id);
            $exchange = $this->db->get('app_exchanges')->row();

            // Invalid
            // if ( ! $exchange OR $exchange_transaction_id != $pay_result->factorNumber)
            // {
            // 	$this->session->set_flashdata('message', 'کد تراکنش نامعتبر است');
            // 	$this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));
            // 	$this->_render_page('auth/users/return_bank_mellat', $this->data);
            // 	return FALSE;
            // }

            if ( ! $exchange OR $exchange_transaction_id != $exchange->transaction_id)
            {
                $this->session->set_flashdata('message', 'کد تراکنش نامعتبر است');
                $this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));
                $this->_render_page('auth/users/return_bank_mellat', $this->data);
                return FALSE;
            }

            // Already Paid
            if ($exchange->completed > 0)
            {
                $this->session->set_flashdata('message', 'این تراکنش قبلا پرداخت شده');
                $this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));
                $this->_render_page('auth/users/return_bank_mellat', $this->data);
                return FALSE;
            }

            // Success

            $bank_invoice_id               = preg_replace('/[^\w-]/', '', $exchange_transaction_id);
            $bank_transaction_reference_id = preg_replace('/[^\w-]/', '', $_POST['transid']);
            // $bank_invoice_id               = preg_replace('/[^\w-]/', '', $pay_result->factorNumber);
            // Update Bank Info
            $data = array(
                "bank_transaction_reference_id" => $bank_transaction_reference_id,
                "bank_invoice_id"               => $bank_invoice_id
            );
            $this->db->where('transaction_id', $transaction_id);
            $this->db->update('app_exchanges', $data);

            // Get Info to check
            $this->db->select('*');
            // $this->db->where('transaction_id', $pay_result->factorNumber);
            $this->db->where('transaction_id', $transaction_id);
            $transaction = $this->db->get('app_exchanges')->row();
            $send_amount = $transaction->amount;

            // Check amount
            // $check_rials = bcmul("{$transaction->rials}",'10',0);
            // if ( ! $transaction OR ! is_numeric($transaction->rials) OR $pay_result->amount != $check_rials)
            // {
            // 	$this->data['message'] = 'سیستم در تایید مبلغ دریافتی ناموفق بود لطفا با مدیریت جهت تایید پرداخت تماس بگیرید';
            // 	$this->_render_page('auth/users/return_bank_mellat', $this->data);
            // 	return FALSE;
            // }

            if ( ! $transaction OR $transaction->ecurrency != $currency)
            {
                $this->session->set_flashdata('message', '2خطایی رخ داده و نوع اکانت معتبر نیست لطفا در صورت واریز وجه با مدیریت تماس بگیرید');
            }
            else
            {
                // Do things

                // Try to insert into Instant Pay table (Unique) and check
                $this->load->model('our_model');
                $instapay = $this->our_model->is_system_allowed_to_send_money($transaction_id, $transaction->user_id);

                // Load Encryption
                $this->load->library('encrypt');

                if ($currency == 'bitcoin' && $instapay > 0)
                {
                    $this->our_model->pay_bitcoin($transaction->user_id, $transaction_id);
                }

                elseif ($currency == 'perfectmoney' && $instapay > 0)
                {
                    $this->our_model->pay_perfectmoney($transaction->user_id, $transaction_id);
                }

                elseif ($currency == 'webmoney' && $instapay > 0)
                {
                    // Load Webmoney Model
                    $this->load->model('webmoney_model');
                    $this->webmoney_model->pay_webmoney($transaction->user_id, $transaction_id);
                }
                else
                {
                    $webpurse_email = $this->config->item('webpurse_email');
                    mail("$webpurse_email", "New Pending Exchange (Buy {$currency})", $transaction_id, 'From: info@webpurse.org');
                    $this->session->set_flashdata('message', 'مبلغ ریالی دریافت شد، مدیریت در اسرع وقت مبلغ را به حساب ارزی ذخیره شده شما واریز خواهد کرد');
                }

            }

            $this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));
            $this->_render_page('auth/users/return_bank_mellat', $this->data);
        }




        function sell()
        {
            // Get segment
            $currency = html_escape($this->uri->segment(3));
            if ($this->uri->segment(3) === FALSE OR ($currency != 'perfectmoney' && $currency != 'bitcoin' && $currency != 'okpay' && $currency != 'paypal' && $currency != 'webmoney' && $currency != 'skrill' && $currency != 'btce'))
            {
                $this->session->set_flashdata('message', 'نوع اکانت معتبر نیست');
                redirect('auth/exchange', 'refresh');
            }

            $this->db->select('*');
            $this->db->where('english_name', $currency);
            $curr_info = $this->db->get('app_prices')->row();

            if (intval($curr_info->temp_disable) === 1)
            {
                $this->session->set_flashdata('message', 'در حال حاضر به دلیل عدم اتصال به سرور این ارز موقتا غیر فعال است لطفا طی ساعات آینده تلاش بفرمایید');
                redirect('auth/exchange', 'refresh');
            }

            $this->data['price']        = $this->data['sell_price'] = $curr_info->sell_price;
            $this->data['english_name'] = $curr_info->english_name;
            $this->data['persian_name'] = $curr_info->persian_name;
            $this->data['min_amount']   = $curr_info->sell_min_amount;
            $this->data['max_amount']   = $curr_info->sell_max_amount;
            $this->data['total_max_amount'] = $curr_info->sell_max_amount;
            $this->data['description']  = $curr_info->description;
            $this->data['unit']         = 'بیتکوین';
            $sell_price = $curr_info->sell_price;

            if ($curr_info->english_name != 'bitcoin')
            {
                $this->data['unit']       = 'دلار';
                $this->data['min_amount'] = $this->truncate_number($this->data['min_amount'], 2);
                $this->data['max_amount'] = $this->truncate_number($this->data['max_amount'], 2);
            }

            $user = $this->ion_auth->user()->row();
            $user_id = intval($user->id);
            $transaction_id = date('YmdHis') . "{$user_id}" . rand(1,9);
            $english_name = $curr_info->english_name;

            $this->data['extra_info'] = array(
                'name'         => 'extra_info',
                'id'           => 'extra_info',
                'value'        => ''
            );

            // Max amount is daily, check and set users limit based on what he used
            $this->data['has_another_exchange'] = FALSE;
            $sum_today = $this->db->query("SELECT IFNULL(SUM(amount),0) AS total FROM app_exchanges WHERE user_id = {$user_id} AND ecurrency = '{$currency}' AND buy_or_sell = 'sell' AND DATE(date) = CURDATE()")->row_array();
            if ( ! $sum_today OR ! isset($sum_today['total']) OR ! is_numeric($sum_today['total']))
            {
                echo "Error #784<br>";
                die('خطایی رخ داد لطفا بعدا امحان کنید یا با مدیریت تماس بگیرید');
            }
            $today_used = $sum_today['total'];
            // New Max
            if ($today_used > 0)
            {
                $this->data['has_another_exchange'] = TRUE;
                $this->data['max_amount'] = bcsub("{$this->data['max_amount']}","{$today_used}",8);
                if ($curr_info->english_name != 'bitcoin')
                {
                    $this->data['max_amount'] = $this->truncate_number($this->data['max_amount'], 2);
                }
            }
            if ($this->data['max_amount'] < 0)
            {
                $this->data['max_amount'] = 0;
            }

            $bank_accs = array();
            $select_bank = '';
            $this->data['no_bank'] = TRUE;
            $this->data['no_card'] = TRUE;

            if (strlen($user->card_acc) > 1)
            {
                $this->data['no_card'] = FALSE;
                $this->data['no_bank'] = FALSE;
                $select_bank = 'card';
                $bank_accs['card'] = "کارت شتاب".' '."&#x200E;{$user->card_acc}";
            }

            if (strlen($user->sheba_acc) > 1)
            {
                $this->data['no_bank'] = FALSE;
                $select_bank = 'sheba';
                $bank_accs['sheba'] = "شبا".' '."&#x200E;{$user->sheba_acc}";
            }

            if (strlen($user->saman_acc) > 1)
            {
                $this->data['no_bank'] = FALSE;
                $select_bank = 'saman';
                $bank_accs['saman'] = "سامان".' '."&#x200E;{$user->saman_acc}";
            }

            if (strlen($user->mellat_acc) > 1)
            {
                $this->data['no_bank'] = FALSE;
                $select_bank = 'mellat';
                $bank_accs['mellat'] = "ملت".' '."&#x200E;{$user->mellat_acc}";
            }

            $this->data['user_bank_info'] = array(
                'name'         => 'user_bank_info',
                'id'           => 'user_bank_info',
                'options'      => $bank_accs,
                'style'        => 'text-align:right;direction:ltr;font-family:Tahoma;font-size:13px;min-width:200px;',
                'selected'     => array($select_bank)
            );

            $this->form_validation->set_rules('amount', 'مبلغ', "required|numeric|greater_than_equal_to[{$this->data['min_amount']}]|less_than_equal_to[{$this->data['max_amount']}]");
            $this->form_validation->set_rules('user_bank_info', 'حساب یا کارت', "required|in_list[mellat,saman,card,sheba]");
            $this->form_validation->set_rules('extra_info', 'مشخصات واریز و توضیحات', "required");

            if (isset($_POST) && !empty($_POST))
            {
                if ($this->form_validation->run() === TRUE)
                {
                    $date       = date('Y-m-d H:i:s');
                    $ip_address = $this->input->ip_address();
                    $amount     = $this->input->post('amount');
                    $extra_info = html_escape($this->input->post('extra_info', TRUE));

                    $which_bank = "{$select_bank}_acc";
                    $bank_acc = $user->$which_bank;
                    $user_bank_info = ucfirst(html_escape($this->input->post('user_bank_info', TRUE).": {$bank_acc}"));

                    $rials = bcmul("$sell_price","$amount",0);

                    // Check if amount more or less
                    if ($amount > $this->data['max_amount'] OR $amount < $this->data['min_amount'])
                    {
                        $this->session->set_flashdata('message', 'مبلغ وارد شده کمتر یا بیشتر از خرید سایت است');
                        redirect('auth/sell', 'refresh');
                    }

                    $data = array(
                        'buy_or_sell' => 'sell',
                        'date' => $date,
                        'amount' => $amount,
                        'rials' => $rials,
                        'user_id' => $user_id,
                        'ecurrency' => $this->data['english_name'],
                        'ip_address' => $ip_address,
                        'user_bank_info' => $user_bank_info,
                        'extra_info' => $extra_info,
                        'transaction_id' => $transaction_id
                    );

                    $insert    = $this->db->insert('app_exchanges', $data);

                    if ($insert)
                    {
                        $webpurse_email = $this->config->item('webpurse_email');
                        mail("$webpurse_email", "New Pending Exchange (Sell {$english_name})", $transaction_id, 'From: info@webpurse.org');
                        redirect('auth/sell_done', 'refresh');
                    }
                    else
                    {

                    }
                }
            }

            $this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));
            $this->_render_page('auth/users/sell', $this->data);
        }



        function history()
        {
            $user_id = $this->ion_auth->user()->row()->id;

            $this->db->select('*');
            $this->db->where('user_id', $user_id);
            $this->data['transactions'] = $this->db->get('app_exchanges')->result_array();

            $this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));

            foreach($this->data['transactions'] as $key => $trans)
            {
                if($trans['ecurrency'] != 'bitcoin')
                {
                    $this->data['transactions'][$key]['amount'] = $this->truncate_number($this->data['transactions'][$key]['amount']);
                    $this->data['transactions'][$key]['unit']   = 'دلار';
                }
                else
                {
                    $this->data['transactions'][$key]['unit']   = 'بیتکوین';
                }
            }

            $this->_render_page('auth/users/history', $this->data);
        }


        function error_bank()
        {
            $this->_render_page('auth/users/error_bank', $this->data);
        }


        function sell_done()
        {
            $this->_render_page('auth/users/sell_done', $this->data);
        }


        function comment()
        {
            if (!$this->ion_auth->logged_in())
            {
                redirect('auth/logout', 'refresh');
            }

            // Get Data
            $user_id = $this->ion_auth->user()->row()->id;
            $this->db->select('*');
            $this->db->where('id', $user_id);
            $get_data = $this->db->get('app_users')->row();
            $fname = $get_data->first_name;
            $lname = $get_data->last_name;
            $name  = $fname . ' ' . $lname;
            $name  = html_escape($name);

            // validate form input
            $this->form_validation->set_rules('comment', 'نظر شما', 'required');

            if (isset($_POST) && !empty($_POST) && isset($_POST['comment']))
            {
                if ($this->form_validation->run() === TRUE)
                {
                    $comment = html_escape($this->input->post('comment', TRUE));
                    $ip      = $this->input->ip_address();

                    // Is inserted?
                    $this->db->where('user_id',$user_id);
                    $q = $this->db->get('app_comments');

                    $data = array(
                        'user_id'      => $user_id,
                        'name'         => $name,
                        'ip'           => $ip,
                        'comment'      => $comment,
                        'allow'        => 0
                    );

                    if ( $q->num_rows() > 0 )
                    {

                        $this->db->where('user_id', $user_id);
                        $comment_update = $this->db->update('app_comments', $data);
                    }
                    else
                    {
                        $comment_update = $this->db->insert('app_comments', $data);
                    }

                    if($comment_update)
                    {
                        $this->session->set_flashdata('message', 'با موفقیت ذخیره شد');
                    }
                    else
                    {
                        $this->session->set_flashdata('message', 'خطا در تغییر');
                    }
                    redirect("auth/comment", 'refresh');
                }
            }

            // set the flash data error message if there is one
            $this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));

            $this->data['comment'] = array(
                'name'  => 'comment',
                'id'    => 'comment',
                'type'  => 'text',
                'value' => $this->form_validation->set_value('comment', ''),
            );
            $this->_render_page('auth/users/comment', $this->data);
        }



        function your_accounts()
        {
            $user_id = $this->ion_auth->user()->row()->id;

            if (intval($user_id) <= 1 OR ! $this->ion_auth->logged_in())
            {
                $this->session->set_flashdata('message', 'خطایی رخ داد');
                redirect('auth/', 'refresh');
            }

            $this->data['title'] = $this->lang->line('edit_group_title');

            $this->db->select('*');
            $this->db->where('id', $user_id);
            $your_accounts = $this->db->get('app_users')->row();

            // set the flash data error message if there is one
            $this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));

            // pass accounts to the view
            $this->data['your_accounts'] = $your_accounts;

            $this->_render_page('auth/users/your_accounts', $this->data);
        }


        function edit_your_accounts()
        {
            $user_id = $this->ion_auth->user()->row()->id;

            if (intval($user_id) !== intval($_SESSION['user_id']) OR intval($user_id) <= 1)
            {
                $this->session->set_flashdata('message', 'خطایی رخ داد');
                redirect('auth/logout', 'refresh');
            }

            if (intval($user_id) <= 1 OR ! $this->ion_auth->logged_in())
            {
                $this->session->set_flashdata('message', 'خطایی رخ داد');
                redirect('auth/', 'refresh');
            }

            // Get Name of ACC from segment
            $acc = html_escape($this->uri->segment(3));
            if ($this->uri->segment(3) === FALSE OR ($acc != 'perfectmoney' && $acc != 'bitcoin' && $acc != 'okpay' && $acc != 'paypal' && $acc != 'webmoney' && $acc != 'skrill' && $acc != 'btce' && $acc != 'mellat' && $acc != 'saman' && $acc != 'card' && $acc != 'sheba' && $acc != 'others'))
            {
                $this->session->set_flashdata('message', 'نوع اکانت معتبر نیست');
                redirect('auth/your_accounts', 'refresh');
            }

            $full_acc = $acc . '_acc';
            $this->db->select($full_acc);
            $this->db->where('id', $user_id);
            $your_accounts = $this->db->get('app_users')->row();
            $this_account  = html_escape($your_accounts->$full_acc);

            // validate form input
            // If email
            if ($acc == 'paypal' OR $acc == 'skrill')
            {
                $this->form_validation->set_rules('new_account', 'ایمیل حساب شما', 'required|valid_email');
            }

            if ($acc == 'perfectmoney' OR $acc == 'bitcoin' OR $acc == 'webmoney' OR $acc == 'btce' OR $acc == 'okpay')
            {
                $this->form_validation->set_rules('new_account', 'شماره حساب شما', 'required|alpha_numeric|max_length[40]|min_length[5]');
            }

            if ($acc == 'mellat' OR $acc == 'saman' OR $acc == 'card' OR $acc == 'sheba')
            {
                $this->form_validation->set_rules('new_account', 'شماره حساب شما', 'required|alpha_dash|max_length[40]|min_length[5]');
            }

            if ($acc == 'others')
            {
                $this->form_validation->set_rules('new_account', 'شماره حساب شما', 'required|alpha_dash|max_length[63]|min_length[5]');
            }

            if (isset($_POST) && !empty($_POST))
            {
                if ($this->form_validation->run() === TRUE)
                {
                    $data = array(
                        "{$full_acc}" => html_escape($this->input->post('new_account', TRUE))
                    );
                    $this->db->where('id', $user_id);
                    $account_update = $this->db->update('app_users', $data);

                    if($account_update)
                    {
                        $this->session->set_flashdata('message', 'با موفقیت ذخیره شد');
                    }
                    else
                    {
                        $this->session->set_flashdata('message', 'خطا در تغییر قیمت');
                    }
                    redirect("auth/your_accounts", 'refresh');
                }
            }

            // set the flash data error message if there is one
            $this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));

            $this->data['acc_name'] = html_escape($acc);

            $this->data['new_account'] = array(
                'name'  => 'new_account',
                'id'    => 'new_account',
                'type'  => 'text',
                'style'  => 'width:300px;font-family:Tahoma;text-align:left;direction:ltr;',
                'value' => html_escape($your_accounts->$full_acc)
            );

            $this->_render_page('auth/users/edit_your_accounts', $this->data);
        }


















        function _require_second_password()
        {
            // Get Second Password and Salt from Database
            if ($this->input->post('second_pass') OR isset($_SESSION['sec_token']))
            {
                $pass_salt   = $this->db->query('SELECT salt,second_password FROM app_settings WHERE id=1')->row();
                $db_password = $pass_salt->second_password;
                $db_salt     = $pass_salt->salt;
                $ip          = $this->input->ip_address();
            }

            $this->data['pendings']           = '?';
            $this->data['new_pending_member'] = '?';

            // Get count of pending exchanges
            $this->data['pendings'] = $this->db->query("SELECT count(id) AS pendings FROM app_exchanges WHERE completed=0 LIMIT 1")->row()->pendings;

            // Get count of new pending verified users today
            $this->data['new_pending_members'] = $this->db->query("SELECT count(id) AS new_pending_members FROM app_users WHERE from_unixtime(created_on, '%Y-%m-%d') = DATE(NOW())")->row()->new_pending_members;

            // Get count of admin bruters
            $ip_ban = $this->db->query("SELECT count(ip_address) AS bruters , sum(count) AS bruts FROM app_ip_ban WHERE page='admin_second_password' AND count > 0")->row();

            $this->data['bruters'] = $ip_ban->bruters;
            $this->data['bruts'] = $ip_ban->bruts;


            // Check Session
            if (isset($_SESSION['sec_token']))
            {
                $session_token = $_SESSION['sec_token'];
                $real_token    = hash('sha256', "{$db_password}{$ip}");

                // Check Token
                if (strcmp($real_token, $session_token) === 0)
                {
                    return TRUE;
                }
                else
                {
                    unset($_SESSION['sec_token']);
                    $this->session->set_flashdata('message', 'رمز دوم اشتباه است');
                }
            }

            // Delay brute
            usleep(600000);

            // If Posted
            if ($this->input->post('second_pass'))
            {
                if ($this->_valid_csrf_nonce() === FALSE)
                {
                    show_error($this->lang->line('error_csrf'));
                    die();
                }

                $posted_pass = $this->input->post('second_pass');
                $hash        = hash('sha256', "{$db_salt}{$posted_pass}");

                // Check posted password
                if (strcmp($hash, $db_password) === 0)
                {
                    // Clear if IP Logged
                    $ip_address = htmlspecialchars(trim($this->input->ip_address()));
                    $log_fail = $this->db->query("UPDATE app_ip_ban SET count = 0 WHERE ip_address = ".$this->db->escape($ip_address));

                    $token = hash('sha256', "{$hash}{$ip}");
                    $this->session->set_userdata('sec_token', $token);
                    return TRUE;
                }
                else
                {
                    // Log IP
                    $ip_address = htmlspecialchars(trim($this->input->ip_address()));
                    $log_fail = $this->db->query("INSERT INTO app_ip_ban (ip_address, page) VALUES (".$this->db->escape($ip_address).", 'admin_second_password') ON DUPLICATE KEY UPDATE last_date = NOW(), count = count + 1");

                    // Should we ban IP?
                    $count_fail = $this->db->query("SELECT count FROM app_ip_ban WHERE ip_address=".$this->db->escape($ip_address)." LIMIT 1")->row()->count;
                    if ($count_fail > 5)
                    {
                        $this->db->query("UPDATE app_ip_ban SET ban = 1 WHERE ip_address = ".$this->db->escape($ip_address));
                        die('تعداد ورود اشتباه زیاد است ، آی پی شما مسدود و به مدیریت گزارش شد');
                    }
                    if ($count_fail > 2)
                    {
                        echo '<p style="color:red;font-size:13px;margin-bottom:10px;">هشدار! فقط ۱ یا ۲ بار دیگر فرصت دارید</p>';
                    }

                    unset($_SESSION['sec_token']);
                    $this->session->set_flashdata('message', 'رمز دوم اشتباه است');

                    // Second Password was wrong, sleep again
                    sleep(2);
                }
            }

            $this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');
            $this->data['csrf'] = $this->_get_csrf_nonce();
            $this->_render_page('auth/second_password', $this->data);
            return FALSE;
        }



        // Check if user IP is in IP Black list = die
        function _ip_blacklist()
        {
            $ip_address = $this->input->ip_address();

            $blocked = $this->db->query("SELECT ban FROM app_ip_ban WHERE ip_address=".$this->db->escape($ip_address)." LIMIT 1")->row();

            if (isset($blocked->ban) && $blocked->ban == 1)
            {
                echo "{$ip_address}<br>";
                die('آی پی آدرس شما مسدود شده است ، لطفا با مدیریت تماس بگیرید');
            }
            else
            {
                return TRUE;
            }
        }



        // SMS Verify
        function _require_sms_verification($user_id)
        {
            // Check if Enabled / Get Settings
            $this->db->select('*');
            $this->db->where('id', 1);
            $settings = $this->db->get('app_settings')->row();
            $require_pin_to_buy = $settings->require_pin_to_buy;

            if (intval($require_pin_to_buy) !== 1 OR (isset($_SESSION['verified_sms']) && $_SESSION['verified_sms'] == 'yes'))
            {
                return TRUE;
            }

            // Get User Mobile Number
            $user_id = intval($user_id);
            $this->db->select('*');
            $this->db->where('id', $user_id);
            $settings = $this->db->get('app_users')->row();
            $mobile = $settings->mobile;

            // Send Code and Check / Set Sessions
            if ( ! isset($_SESSION['verify_code']))
            {
                // Set Sessions
                $code = rand(100000,9999999);
                $now  = strtotime("+2 seconds");
                $this->session->set_userdata('verify_code_time', $now);
                $this->session->set_userdata('verify_code', $code);

                // Send SMS
                $api_key       = $this->config->item('sms_api_code');
                $sender_number = $this->db->select('sms_number')->from('app_settings')->where('id', 1)->get()->row()->sms_number;

                $message = "{$code}";
                $json_url = "https://api.kavenegar.com/v1/{$api_key}/sms/send.json?receptor={$mobile}&sender={$sender_number}&message={$message}";

                $json_data = $this->curl_get_content($json_url);
                $data = json_decode($json_data, TRUE);

                if ( ! isset($data['return']['status']) OR intval($data['return']['status']) !== 200)
                {
                    $this->session->set_flashdata('message', 'خطایی هنگام ارسال کد به شماره شما رخ داد لطفا بعدا مجددا امتحان کنید یا با مدیریت تماس بگیرید');
                }
            }

            // If Posted
            if ($this->input->post('verify_code'))
            {
                $posted_code = $this->input->post('verify_code');
                $saved_code  = $_SESSION['verify_code'];

                // Delay Checking for 2 seconds
                sleep(2);

                // Check posted password
                if (strcmp($posted_code, $saved_code) === 0)
                {
                    $this->session->set_userdata('verified_sms', 'yes');
                    return TRUE;
                }
                else
                {
                    $this->session->set_flashdata('message', 'کد وارد شده اشتباه است');
                }
            }

            $this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');
            $this->_render_page('auth/verify_with_sms', $this->data);
            return FALSE;
        }



        function _get_csrf_nonce()
        {
            $this->load->helper('string');
            $key   = random_string('alnum', 8);
            $value = random_string('alnum', 20);
            $this->session->set_flashdata('csrfkey', $key);
            $this->session->set_flashdata('csrfvalue', $value);

            return array($key => $value);
        }

        function _valid_csrf_nonce()
        {
            if ($this->input->post($this->session->flashdata('csrfkey')) !== FALSE &&
                $this->input->post($this->session->flashdata('csrfkey')) == $this->session->flashdata('csrfvalue'))
            {
                return TRUE;
            }
            else
            {
                return FALSE;
            }
        }

        function _render_page($view, $data=null, $returnhtml=false)//I think this makes more sense
        {

            $this->viewdata = (empty($data)) ? $this->data: $data;

            $view_html = $this->load->view($view, $this->viewdata, $returnhtml);

            if ($returnhtml) return $view_html;//This will return html on 3rd argument being true
        }

        // Hazfe raghame ashary ezafe: 1.215000 => 1.215
        function truncate_number( $number, $precision = 2)
        {
            // Return if 0
            if ($number < 0.000001)
            {
                return 0;
            }
            // Are we negative?
            $negative = $number / abs($number);
            // Cast the number to a positive to solve rounding
            $number = abs($number);
            // Calculate precision number for dividing / multiplying
            $precision = pow(10, $precision);
            // Run the math, re-applying the negative value to ensure returns correctly negative / positive
            return floor( $number * $precision ) / $precision * $negative;
        }

        function makeXMLTree($data)
        {
            $ret = array();
            $parser = xml_parser_create();
            xml_parser_set_option($parser,XML_OPTION_CASE_FOLDING,0);
            xml_parser_set_option($parser,XML_OPTION_SKIP_WHITE,1);
            xml_parse_into_struct($parser,$data,$values,$tags);
            xml_parser_free($parser);
            $hash_stack = array();
            foreach ($values as $key => $val)
            {
                switch ($val['type'])
                {
                    case 'open':
                        array_push($hash_stack, $val['tag']);
                        break;
                    case 'close':
                        array_pop($hash_stack);
                        break;
                    case 'complete':
                        array_push($hash_stack, $val['tag']);
                        // uncomment to see what this function is doing
                        $thekey = implode($hash_stack, "][");
                        $thekey = str_replace('actionResult][','',$thekey);
                        $thekey = str_replace('resultObj][','',$thekey);
                        if(isset($val['value']))
                        {
                            $ret[$thekey] = $val['value'];
                        }
                        array_pop($hash_stack);
                        break;
                }
            }
            return $ret;
        }


        /* ------------------------------------- CURL POST TO HTTPS --------------------------------- */
        function post2https($fields_arr, $url)
        {
            //url-ify the data for the POST
            $fields_string = '';
            foreach($fields_arr as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
            $fields_string = substr($fields_string, 0, -1);

            //open connection
            $ch = curl_init();

            //set the url, number of POST vars, POST data
            curl_setopt($ch,CURLOPT_URL,$url);
            curl_setopt($ch,CURLOPT_POST,count($fields_arr));
            curl_setopt($ch,CURLOPT_POSTFIELDS,$fields_string);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);


            //execute post
            $res = curl_exec($ch);

            //close connection
            curl_close($ch);
            return $res;
        }

        protected function get_content($url)
        {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            $data = curl_exec($ch);

            if (curl_errno($ch))
            {
                $err = curl_error($ch);
            }
            else
            {
                // check the HTTP status code of the request
                $result_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if ($result_status != 200)
                {

                }
            }

            curl_close($ch);

            return $data;
        }

        protected function curl_get_content($URL)
        {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_URL, $URL);
            $data = curl_exec($ch);
            curl_close($ch);
            return $data;
        }

        protected function curl_post($url, $params)
        {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
            ]);
            $res = curl_exec($ch);
            curl_close($ch);

            return $res;
        }


    }



// End of Auth