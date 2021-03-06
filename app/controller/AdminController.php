<?php

class AdminController
{
    public function login()
    {
    

        $view = new View();    
        $view->render('login', [
            "message" => ""
        ]);
    }

    public function registration()
    {
        $view = new View();
        $view->render('registration',["message"=>""]);
       
    }

    public function register()
    {

        $db = Db::connect();
        $statement = $db->prepare("insert into user (firstname,lastname,email,pass) values (:firstname,:lastname,:email,:pass)");
        $statement->bindValue('firstname', Request::post("firstname"));
        $statement->bindValue('lastname', Request::post("lastname"));
        $statement->bindValue('email', Request::post("email"));
        $statement->bindValue('pass', password_hash(Request::post("pass"),PASSWORD_DEFAULT));
        $statement->execute();

        Session::getInstance()->logout();
        $view = new View();
        $view->render('login',["message"=>""]);
       
    }

    public function delete($post)
    {

        $db = Db::connect();
        $db->beginTransaction();
        $statement = $db->prepare("delete from comment where post=:post");
        $statement->bindValue('post', $post);
        $statement->execute();

        $statement = $db->prepare("delete from likes where post=:post");
        $statement->bindValue('post', $post);
        $statement->execute();

        $statement = $db->prepare("delete from post where id=:post");
        $statement->bindValue('post', $post);
    
        $statement->execute();

        $db->commit();
        
        $this->index();
       
    }

    public function comment($post)
    {

        $db = Db::connect();
        $statement = $db->prepare("insert into comment (post,user, content) values (:post,:user,:content)");
        $statement->bindValue('post', $post);
        $statement->bindValue('user', Session::getInstance()->getUser()->id);
        $statement->bindValue('content', Request::post("content"));
        $statement->execute();
        
        $view = new View();

        $view->render('view', [
            "post" => Post::find($post)
        ]);
       
    }


    public function like($post)
    {

        $db = Db::connect();
        $statement = $db->prepare("insert into likes (post,user) values (:post,:user)");
        $statement->bindValue('post', $post);
        $statement->bindValue('user', Session::getInstance()->getUser()->id);
        $statement->execute();
        
        $this->index();
       
    }


    public function authorize()
    {
//ne dostaju kontrole
        $db = Db::connect();
        $statement = $db->prepare("select id ,firstname,lastname,email, pass from user where email=:email");
        $statement->bindValue('email', Request::post("email"));
        $statement->execute();


        if($statement->rowCount()>0){
            $user = $statement->fetch();
            if(password_verify(Request::post("password"), $user->pass)){
              
                unset($user->pass);
                
                Session::getInstance()->login($user);

                $this->index();
            }else{
                $view = new View();
                $view->render('login',["message"=>"Neispravna kombinacija korisničko ime i lozinka"]);
            }
        }else{
            $view = new View();
            $view->render('login',["message"=>"Neispravan email"]);
        }



       
    }
    public function update($id)
    {
            if(Validator::string(Request::post("firstname")) && Validator::string(Request::post("lastname"))) {
                if(Validator::email(Request::post("email"))) {
                    if(Validator::password(Request::post("newpassword"), Request::post("password"))) {

                        $db = Db::connect();
                        $statement = $db->prepare("update user set firstname = :firstname , lastname= :lastname,
                    email = :email, pass=:pass  where id = :id");
                        $statement->bindValue('firstname', Request::post("firstname"));
                        $statement->bindValue('lastname', Request::post("lastname"));
                        $statement->bindValue('email', Request::post("email"));
                        $statement->bindValue('pass', password_hash(Request::post("newpassword"), PASSWORD_DEFAULT));
                        $statement->bindValue('id', $id);
                        $statement->execute();
                        //update session
                        Session::getInstance()->getUser()->firstname = Request::post("firstname");
                        Session::getInstance()->getUser()->lastname = Request::post("lastname");
                        Session::getInstance()->getUser()->email = Request::post("email");
                        header('Location: ' . App::config('url'));

                    } else {
                        $view = new View();
                        $view->render('updateUser',["message"=>"Wrong password!"]);
                    }
                }
                else {
                    $view = new View();
                    $view->render('updateUser',["message"=>"Wrong email!"]);

                }
            }
            else {
                $view = new View();
                $view->render('updateUser',["message"=>"Wrong insert for first or last name!"]);
            }


    }

    public function logout()
    {
    
        Session::getInstance()->logout();
        $this->index();
    }

    public function json()
    {

        $posts = Post::all();
       //print_r($posts);
        echo json_encode($posts);
    }

    public function index()
    {

        $posts = Post::all();
        $view = new View();
        $view->render('index', [
            "posts" => $posts
        ]);
    }


/* function bulkinsert()
    {
        $db = Db::connect();
        for($i=0;$i<1000;$i++){

            $statement = $db->prepare("insert into post (content,user) values ('DDDD $i',1)");
            $statement->execute();

            $id = $db->lastInsertId();

            for($j=0;$j<10;$j++){

                $statement = $db->prepare("insert into comment (content,user,post) values ('CCCCC $i',1,$id)");
                $statement->execute();


            }

        }


    }*/

   
}