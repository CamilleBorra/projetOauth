<?php

function query($clientId, $redirect, $response, $scope, $allow = null){
    if($allow != null){
        $queryParams= http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirect,
            'response_type' => $response,
            'scope' => $scope,
            'state' => bin2hex(random_bytes(16)),
            'allow_signup' => $allow
        ]); 
    }
    else{
        $queryParams= http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirect,
            'response_type' => $response,
            'scope' => $scope,
            'state' => bin2hex(random_bytes(16)),
        ]); 
    }
    return $queryParams;

}