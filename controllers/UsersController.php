<?php

class UsersController extends MyController
{
    public function getAction($request) {
        # /<id>/friends /1/2
        if(isset($request->url_elements[1])) {
            if (preg_match('/[0-9]+/', $request->url_elements[1])) {
                $user_id = (int)$request->url_elements[1];
                if(isset($request->url_elements[2])) {
                    switch($request->url_elements[2]) {
                    case 'friends':
                        $request->db->query('
                            SELECT u.id, pseudo AS handle
                            FROM users AS u
                            INNER JOIN relations AS r
                                ON r.user_id_2 = u.id
                            WHERE r.user_id_1 = :id
                            UNION
                            SELECT u.id, pseudo
                            FROM users AS u
                            INNER JOIN relations AS r
                                ON r.user_id_1 = u.id
                            WHERE r.user_id_2 = :id
                        ');
                        $request->db->bind(':id', $user_id);
                        $data['data'] = $request->db->fetchall();
                        $data['error'] = false;
                        break;
                    default:
                        $data['data'] = [];
                        $data['error'] = false;
                        // do nothing, this is not a supported action
                        break;
                    }
                } else {
                    $request->db->query('SELECT * FROM users WHERE id = :id');
                    $request->db->bind(':id', $user_id);
                    $data['data'] = $request->db->fetchall();
                    unset($data['data'][0]['password']);
                    $data['error'] = false;
                }
            } else if ($request->url_elements[1] == 'search') {
                if (!isset($request->url_elements[2]))
                    $query = "";
                else
                    $query = $request->url_elements[2];        
                $request->db->query('SELECT id, pseudo AS handle FROM users WHERE pseudo LIKE :handle ORDER BY pseudo ASC');
                $request->db->bind(':handle', '%' . $query . '%');
                $data['data'] = $request->db->fetchall();
                $data['error'] = false;
            }
        } else {
            # Get all users
            $request->db->query('SELECT id, pseudo AS handle FROM users');
            $data['data'] = $request->db->fetchall();
            $data['error'] = false;
        }
        if ($data['error'])
           $data['message'] = 'An error occured'; 
        return $data;
    }
 
    public function postAction($request) {
        $parameters = $request->parameters;
        if (!isset($request->url_elements[2])) {
            if (!isset($parameters['password']) || !isset($parameters['handle'])) {
                $data['error'] = 'Missing parameters. "handle" and "password" are required.';
                $data['data'] = false;
            } else {
                $handle = $parameters['handle'];
                $password = $parameters['password'];
                $request->db->query('SELECT pseudo FROM users WHERE pseudo = :handle');
                $request->db->bind(':handle', $handle);
                $request->db->execute();
                if ($request->db->rowCount() > 0) {
                    $data['error'] = 'This login is not available';
                    $data['data'] = false;
                } else {
                    $request->db->query('INSERT INTO users VALUES ("", :handle, :password, NOW(), NOW(), 0, 0)');
                    $request->db->bind(':handle', $handle);
                    $request->db->bind(':password', $password);
                    $request->db->execute();
                    $data['data']['id'] = $request->db->lastInsertId();
                    $data['error'] = false;
                }
            }
        } else {
            $user_id = $request->url_elements[1];
            switch ($request->url_elements[2]) {
                case "friends":
                    if (!isset($parameters['id'])) {
                        $data['error'] = 'Missing parameter. "id" is required.';
                        $data['data'] = false;
                    } else {
                        $friend_id = $parameters['id'];
                        $request->db->query('SELECT id FROM users WHERE id = :id');
                        $request->db->bind(':id', $friend_id);
                        $request->db->execute();
                        if ($request->db->rowCount() == 0) {
                            $data['error'] = 'This login does not exist';
                            $data['data'] = false;
                        } else {
                            $request->db->query('SELECT id FROM relations WHERE (user_id_1 = :uid AND user_id_2 = :fid) OR (user_id_1 = :fid AND user_id_2 = :uid)');
                            $request->db->bind(':uid', $user_id);
                            $request->db->bind(':fid', $friend_id);
                            $request->db->execute();
                            if($request->db->rowCount() > 0) {
                                $data['error'] = 'User ' . $user_id . ' and user ' . $friend_id . ' are already friends';
                                $data['data'] = false;
                            } else {
                                $request->db->query('INSERT INTO relations VALUES ("", :uid, :fid, 0, NOW(), NOW())');
                                $request->db->bind(':uid', $user_id);
                                $request->db->bind(':fid', $friend_id);
                                $request->db->execute();
                                $data['error'] = false;
                                $data['message'] = 'Friend has been added successfully';
                            }
                        }
                    }
                    break;
                default:
                    $data['data'] = [];
            }
        }
        if ($data['error'])
           $data['message'] = 'An error occured'; 
        return $data;
    }

    public function putAction($request) {
        if(isset($request->url_elements[1])) {
            $user_id = (int)$request->url_elements[1];
            $parameters = $request->parameters;
            if (!isset($parameters['password'])) {
                $data['error'] = 'Missing parameter. "password" is required.';
                $data['data'] = false;
            } else {
                $password = $parameters['password'];
                $request->db->query('SELECT pseudo FROM users WHERE id = :id');
                $request->db->bind(':id', $user_id);
                $request->db->execute();
                if ($request->db->rowCount() == 0) {
                    $data['error'] = 'This login does not exist.';
                    $data['data'] = false;
                } else {
                    $request->db->query('UPDATE users SET password = :password WHERE id = :id');
                    $request->db->bind(':id', $user_id);
                    $request->db->bind(':password', $password);
                    $request->db->execute();
                    $data['error'] = false;
                    $data['message'] = 'User has been updated successfully';
                } 
            }
        } else {
            $data['error'] = 'Missing parameters. "id" is required';
        }
        if ($data['error'])
           $data['message'] = 'An error occured'; 
        return $data;
    }

    public function deleteAction($request) {
        if(isset($request->url_elements[1])) {
            $user_id = (int)$request->url_elements[1];
            $parameters = $request->parameters;
            $request->db->query('SELECT id FROM users WHERE id = :id');
            $request->db->bind(':id', $user_id);
            $request->db->execute();
            if ($request->db->rowCount() == 0) {
                $data['error'] = 'This login does not exist.';
                $data['data'] = false;
            } else {
                $request->db->query('DELETE FROM users WHERE id = :id');
                $request->db->bind(':id', $user_id);
                $request->db->execute();
                $request->db->query('DELETE FROM relations WHERE user_id_1 = :id OR user_id_2 = :id');
                $request->db->bind(':id', $user_id);
                $request->db->execute();
                $data['error'] = false;
                $data['message'] = 'User has been deleted successfully';
            }
        } else {
            $data['error'] = 'Missing parameters. "id" is required';
        }
        if ($data['error'])
           $data['message'] = 'An error occured';
        return $data;
    }
}
