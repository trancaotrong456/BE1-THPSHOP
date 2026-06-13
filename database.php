<?php 
    class Database {
        private $host = "localhost";
        private $username = "root";
        private $password = "";
        private $dbname = "be1";
        public $conn;
        // khởi tạo kết nối
        public function __construct() {
            $this->conn = new mysqli ($this->host, $this->username, $this->password, $this->dbname);
            if ($this->conn->connect_error) {
                die("Ket noi that bai". $this->conn->connect_error );
            }
            $this->conn->set_charset("utf8");
        }
        // truy vấn dữ liệu
        public function select ($sql) {
            return $this->conn->query($sql);
        }
        // thực thi câu lệnh
        public function execute($sql) {
              return $this->conn->query($sql);
        }
        // chuẩn bị câu lệnh
        public function prepare($sql) {
            return $this->conn->prepare($sql);
        }
        // đóng kết nối
        public function close(){
            $this->conn->close();
        }
    }
?>