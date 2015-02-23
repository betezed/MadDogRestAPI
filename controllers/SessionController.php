<?php

class SessionController extends MyController {
    public function postAction($request) {
        $parameters = $request->parameters;
        if (!isset($parameters['handle']) && !isset($parameters['password'])) {
            $data['error'] = 'Missing parameters. "handle" and "password" are required';
            $data['data'] = false;
        } else {
            $handle = $parameters['handle'];
            $password = $parameters['password'];
            $request->db->query('SELECT id, pseudo AS handle FROM users WHERE pseudo = :handle AND password = :password');
            $request->db->bind(':handle', $handle);
            $request->db->bind(':password', $password);
            $result = $request->db->fetchall();
            if ($request->db->rowCount() != 1) {
                $data['error'] = 'Wrong combination login/password';
                $data['data'] = false;
            } else {
                $data['error'] = false;
                $data['data'] = $result; 
            }
        }
        return $data;
    }
}
