<?php

class Grid extends Grid_Events {
  
  function __construct() {
    $this->routes = array();
  }
  
  function __destruct() {
    if (!empty($this->log_file)) {
      fclose($this->log_file);
    }
  }
  
  function setup($path = null, $method = null) {
    $this->setup_library();
    $this->setup_app();
    $this->setup_request($path, $method);
    $this->setup_response();
  }
  
  function setup_library() {
    $dh = opendir(GRID_DIR . '/library');
    while ($lib = readdir($dh)) {
      if (substr($lib, 0, 1) == '.') {
        continue;
      }
      $filename = GRID_DIR . "/library/$lib/setup.php";
      if (file_exists($filename)) {
        require_once $filename;
      }
    }
  }
  
  function setup_app() {
    require_once GRID_DIR . '/app/setup.php';
  }
  
  function setup_request($path = null, $method = null) {
    if (empty($path)) {
      $url = parse_url($_SERVER['REQUEST_URI']);
      $path = $url['path'];
    }
    if (empty($method)) {
      $method = $_SERVER['REQUEST_METHOD'];
    }
    $path = strtolower($path);
    $method = strtolower($method);
    $this->request = new Grid_Request($path, $method);
    $this->request->setup($this->routes);
  }
  
  function setup_response() {
    $this->response = new Grid_Response();
    $this->response->setup($this->request);
  }
  
  function main() {
    $this->fire_event('page_load', $this->response);
    $this->response->respond();
  }
  
  function get($path, $view, $options = null) {
    $this->add_route('get', $path, $view, $options);
  }
  
  function post($path, $view, $options = null) {
    $this->add_route('post', $path, $view, $options);
  }
  
  function add_route($method, $path, $view, $options) {
    $route = array(
      'method' => $method,
      'path' => $path,
      'view' => $view
    );
    if (is_array($options)) {
      foreach ($options as $key => $value) {
        $route[$key] = $value;
      }
    }
    $this->routes[] = $route;
  }
  
  function setup_params($request) {
    $params = $this->process_routes($request);
    $this->orig_request = clone $request;
    $request->set_params($params);
  }
  
  function reroute() {
    $offset = $this->request->route_index + 1;
    $this->request->setup($this->routes, $offset);
    $this->setup_response();
  }
  
  function log($message) {
    if (empty($this->log_file)) {
      $dir = GRID_DIR . '/data';
      if (!is_writable($dir)) {
        echo "Grid: directory '$dir' is not writable";
        exit;
      }
      $this->log_file = fopen("$dir/grid.log", 'a');
    }
    $message = trim($message);
    $timestamp = date('Y-m-d H:i:s');
    fwrite($this->log_file, "[$timestamp] $message\n");
  }
  
}

?>
