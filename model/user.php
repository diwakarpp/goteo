<?php

namespace Goteo\Model {

	use Goteo\Core\Redirection,
        Goteo\Library\Text,
        Goteo\Library\Image;

	class User extends \Goteo\Core\Model {

        public
            $id = false,
            $role = null,
            $email,
            $name,
            $avatar = false,
            $about,
            $contribution,
            $keywords,
            $active,
            $facebook,
            $twitter,
            $linkedin,
            $country,
            $worth,
            $created,
            $modified,
            $interests = array(),
            $webs = array();

	    public function __set($name, $value) {
            $this->$name = $value;
        }

        /**
         * Guardar usuario.
         * Guarda los valores de la instancia del usuario en la tabla.
         *
         * @TODO: Revisar.
         *
         * Reglas:
         *  - id *
         *  - email *
         *  - password
         *
         * @param array $errors     Errores devueltos pasados por referencia.
         * @return bool true|false
         */
        public function save(&$errors = array()) {
            if($this->validate($errors)) {
                // Nuevo usuario.
                if(empty($this->id)) {
                    $insert = true;
                    $this->id = static::idealiza($this->name);
                    $data[':role_id'] = 3; // @FIXME: Provisionalmente: 3 = Usuario
                    $data[':created'] = 'CURRENT_TIMESTAMP';
                    $data[':active'] = false; // @TODO: Requiere activación.
                }
                $data[':id'] = $this->id;

                if(!empty($this->email)) {
                    $data[':email'] = $this->email;
                }

                if(!empty($this->password)) {
                    $data[':password'] = sha1($this->password);
                }

                // Avatar
                if (is_array($this->avatar) && !empty($this->avatar['name'])) {
                    $image = new Image($this->avatar);
                    $image->save();
                    $data[':avatar'] = $image->id;

                    /**
                     * @FIXME Relación NM user_image
                     */
                    if(!empty($image->id)) {
                        self::query("REPLACE user_image (user_id, image_id) VALUES (:user, :image)", array(':user' => $this->id, ':image' => $image->id));
                    }
                }

                // Perfil público
                if(!empty($this->name)) {
                    $data[':name'] = $this->name;
                }

                if(!empty($this->about)) {
                    $data[':about'] = $this->about;
                }

                if(!empty($this->keywords)) {
                    $data[':keywords'] = $this->keywords;
                }

                if(!empty($this->contribution)) {
                    $data[':contribution'] = $this->contribution;
                }

                if(!empty($this->facebook)) {
                    $data[':facebook'] = $this->facebook;
                }

                if(!empty($this->twitter)) {
                    $data[':twitter'] = $this->twitter;
                }

                if(!empty($this->linkedin)) {
                    $data[':linkedin'] = $this->linkedin;
                }

                // Intereses
                $interests = User\Interest::get($this->id);
                if(!empty($this->interests)) {
                    foreach($this->interests as $interest) {
                        if(!in_array($interest, $interests)) {
                            $_interest = new User\Interest();
                            $_interest->id = $interest;
                            $_interest->user = $this->id;
                            $_interest->save($errors);
                            $interests[] = $_interest;
                        }
                    }
                }
                foreach($interests as $key => $interest) {
                    if(!in_array($interest, $this->interests)) {
                        $_interest = new User\Interest();
                        $_interest->id = $interest;
                        $_interest->user = $this->id;
                        $_interest->remove($errors);
                    }
                }

                // Webs
                if(!empty($this->webs)) {
                    // Eliminar
                    $webs = User\Web::get($this->id);
                    foreach($webs as $web) {
                        if(array_key_exists($web->id, $this->webs['remove'])) {
                            $web->remove($errors);
                        }
                    }
                    // Modificar
                    $webs = User\Web::get($this->id);
                    foreach($webs as $web) {
                        if(array_key_exists($web->id, $_POST['user_webs']['edit'])) {
                            $web->user = $this->id;
                            $web->url = $_POST['user_webs']['edit'][$web->id];
                            $web->save($errors);
                        }
                    }
                    // Añadir
                    foreach($this->webs['add'] as $web) {
                        $_web = new User\Web();
                        $_web->user = $this->id;
                        $_web->url = $web;
                        $_web->save($errors);
                    }
                }

                try {
                    // Construye SQL.
                    if(isset($insert) && $insert == true) {
                        $query = "INSERT INTO user (";
                        foreach($data AS $key => $row) {
                            $query .= substr($key, 1) . ", ";
                        }
                        $query = substr($query, 0, -2) . ") VALUES (";
                        foreach($data AS $key => $row) {
                            $query .= $key . ", ";
                        }
                        $query = substr($query, 0, -2) . ")";
                    }
                    else {
                        $query = "UPDATE user SET ";
                        foreach($data AS $key => $row) {
                            if($key != ":id") {
                                $query .= substr($key, 1) . " = " . $key . ", ";
                            }
                        }
                        $query = substr($query, 0, -2) . " WHERE id = :id";
                    }
                    //$_POST = array();
                    // Ejecuta SQL.
                    return self::query($query, $data);
            	} catch(\PDOException $e) {
                    $errors[] = "Error al actualizar los datos del usuario: " . $e->getMessage();
                    return false;
    			}
            }
            return false;
        }

        /**
         * Validación de datos de usuario.
         *
         * @param array $errors     Errores devueltos pasados por referencia.
         * @return bool true|false
         */
        public function validate(&$errors = array()) {
            // Nuevo usuario.
            if(empty($this->id)) {
                // Nombre de usuario (id)
                if(empty($this->name)) {
                    $errors['username'] = Text::get('error-register-username');
                }
                else {
                    $id = self::idealiza($this->name);
                    $query = self::query('SELECT id FROM user WHERE id = ?', array($id));
                    if($query->fetchColumn()) {
                        $errors['username'] = Text::get('error-register-user-exists');
                    }
                }

                // E-mail
                if(!empty($this->email)) {
                    $query = self::query('SELECT email FROM user WHERE email = ?', array($this->email));
                    if($query->fetchObject()) {
                        $errors['email'] = Text::get('error-register-email-exists');
                    }
                }
                else {
                    $errors['email'] = Text::get('error-register-email');
                }

                // Contraseña
                if(!empty($this->password)) {
                    if(strlen($this->password)<8) {
                        $errors['password'] = Text::get('error-register-short-password');
                    }
                }
                else {
                    $errors['password'] = Text::get('error-register-pasword');
                }
                return empty($errors);
            }
            // Modificar usuario.
            else {
                // Contraseña
                if(!empty($this->password)) {
                    if(strlen($this->password)<8) {
                        $errors['password'] = Text::get('error-register-short-password');
                    }
                }

                if (empty($this->name)) {
                    $errors['name'] = Text::get('validate-user-field-name');
                }
                if (is_array($this->avatar) && !empty($this->avatar['name'])) {
                    $image = new Image($this->avatar);
                    $_err = array();
                    $image->validate($_err);
                    $errors['avatar'] = $_err['image'];
                }
                elseif(!is_object($this->avatar)) {
                    $errors['avatar'] = Text::get('validate-user-field-avatar');
                }
                if (empty($this->about)) {
                    $errors['about'] = Text::get('validate-user-field-about');
                }
                $keywords = explode(',', $this->keywords);
                if (sizeof($keywords) < 5) {
                    $errors['keywords'] = Text::get('validate-user-field-keywords');
                }
                if (empty($this->contribution)) {
                    $errors['contribution'] = Text::get('validate-user-field-contribution');
                }
                if (empty($this->interests)) {
                    $errors['interests'] = Text::get('validate-user-field-interests');
                }
                if (empty($this->webs)) {
                    $errors['webs'] = Text::get('validate-user-field-webs');
                }
                else {
                    if(isset($this->webs['add'])) {
                        foreach($this->webs['add'] as $index => $web) {
                            if(empty($web)) {
                                unset($this->webs['add'][$index]);
                            }
                        }
                    }
                }
                if (empty($this->facebook)) {
                    $errors['facebook'] = Text::get('validate-user-field-facebook');
                }
                if (empty($this->twitter)) {
                    $errors['twitter'] = Text::get('validate-user-field-twitter');
                }
                if (empty($this->linkedin)) {
                    $errors['linkedin'] = Text::get('validate-user-field-linkedin');
                }
            }

            return (empty($errors['email']) && empty($errors['password']));
        }

        /**
         * Usuario.
         *
         * @param string $id    Nombre de usuario
         * @return obj|false    Objeto de usuario, en caso contrario devolverÃ¡ 'false'.
         */
        public static function get ($id) {
            try {
                $query = static::query("
                    SELECT
                        id,
                        role_id AS role,
                        email,
                        name,
                        avatar,
                        about,
                        contribution,
                        keywords,
                        facebook,
                        twitter,
                        linkedin,
                        active,
                        worth,
                        created,
                        modified
                    FROM user
                    WHERE id = :id
                    ", array(':id' => $id));
                $user = $query->fetchObject(__CLASS__);
                $user->avatar = Image::get($user->avatar);
                $user->interests = User\Interest::get($id);
                $user->webs = User\Web::get($id);
                return $user;
            } catch(\PDOException $e) {
                return false;
            }
        }

        /**
         * Lista de usuarios.
         *
         * @param  bool $visible    true|false
         * @return mixed            Array de objetos de usuario activos|todos.
         */
        public static function getAll($visible = true) {
            $query = self::query("SELECT * FROM user WHERE active = ?", array($visible));
            return $query->fetchAll(__CLASS__);
        }

		/**
		 * Validación de usuario.
		 *
		 * @param string $username Nombre de usuario
		 * @param string $password ContraseÃ±a
		 * @return obj|false Objeto del usuario, en caso contrario devolverÃ¡ 'false'.
		 */
		public static function login($username, $password) {
			$query = self::query("
				SELECT
					id
				FROM user
				WHERE BINARY id = :username
				AND BINARY password = :password",
				array(
					':username' => trim($username),
					':password' => sha1($password)
				)
			);
			if($row = $query->fetch()) {
			    return static::get($row['id']);
			}
		}

		/**
		 * Comprueba si el usuario está identificado.
		 *
		 * @return boolean
		 */
		public static function isLogged() {
			return !empty($_SESSION['user']);
		}

		/**
		 * Refresca la sesión.
		 * (Utilizar después de un save)
		 */
		public static function flush() {
    		if(static::isLogged()) {
    			return $_SESSION['user'] = self::get($_SESSION['user']->id);
    		}
    	}

	}
}