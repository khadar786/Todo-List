<?php
class DB
{
    private $link = null;
    public $filter;
    static $inst = null;
    public static $counter = 0;    
 
    public function log_db_errors( $error, $query )
    {
        $message = '<p>Error at '. date('Y-m-d H:i:s').':</p>';
        $message .= '<p>Query: '. htmlentities( $query ).'<br />';
        $message .= 'Error: ' . $error;
        $message .= '</p>';

        if( defined( 'SEND_ERRORS_TO' ) )
        {
            $headers  = 'MIME-Version: 1.0' . "\r\n";
            $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
            $headers .= 'To: Admin <'.SEND_ERRORS_TO.'>' . "\r\n";
            $headers .= 'From: Yoursite <system@'.$_SERVER['SERVER_NAME'].'.com>' . "\r\n";

            //mail( SEND_ERRORS_TO, 'Database Error', $message, $headers );   
        }
        else
        {
            trigger_error( $message );
        }

        if( !defined( 'DISPLAY_DEBUG' ) || ( defined( 'DISPLAY_DEBUG' ) && DISPLAY_DEBUG ) )
        {
            echo $message;   
        }
    }
    
    
    public function __construct()
    {
        mb_internal_encoding( 'UTF-8' );
        mb_regex_encoding( 'UTF-8' );
        mysqli_report( MYSQLI_REPORT_STRICT );
        try {
            $this->link = new mysqli( DB_HOST, DB_USER, DB_PASS, DB_NAME );
            $this->link->set_charset( "utf8" );
        } catch ( Exception $e ) {
            die( 'Unable to connect to database' );
        }
    }

    public function __destruct()
    {
        if( $this->link)
        {
            $this->disconnect();
        }
    }
	
    public function filter( $data )
    {
         if( !is_array( $data ) )
         {
             $data = $this->link->real_escape_string( $data );
             $data = trim( htmlentities( $data, ENT_QUOTES, 'UTF-8', false ) );
         }
         else
         {
             $data = array_map( array( $this, 'filter' ), $data );
         }
         return $data;
     }
	 
     public function escape( $data )
     {
         if( !is_array( $data ) )
         {
             $data = $this->link->real_escape_string( $data );
         }
         else
         {
             $data = array_map( array( $this, 'escape' ), $data );
         }
         return $data;
     }
	 
     public function clean( $data )
     {
         $data = stripslashes( $data );
         $data = html_entity_decode( $data, ENT_QUOTES, 'UTF-8' );
         $data = nl2br( $data );
         $data = urldecode( $data );
         return $data;
     }
	 
     public function db_common( $value = '' )
     {
        if( is_array( $value ) )
        {
            foreach( $value as $v )
            {
                if( preg_match( '/AES_DECRYPT/i', $v ) || preg_match( '/AES_ENCRYPT/i', $v ) || preg_match( '/now()/i', $v ) )
                {
                    return true;
                }
                else
                {
                    return false;
                }
            }
        }
        else
        {
            if( preg_match( '/AES_DECRYPT/i', $value ) || preg_match( '/AES_ENCRYPT/i', $value ) || preg_match( '/now()/i', $value ) )
            {
                return true;
            }
        }
    }
        
    public function query( $query )
    {
        $full_query = $this->link->query( $query );
        if( $this->link->error )
        {
            $this->log_db_errors( $this->link->error, $query );
            return false; 
        }
        else
        {
            return true;
        }
    }
	
    public function table_exists( $name )
    {
         self::$counter++;
         $check = $this->link->query( "SELECT 1 FROM $name" );
         if($check !== false)
         {
             if( $check->num_rows > 0 )
             {
                 return true;
             }
             else
             {
                 return false;
             }
         }
         else
         {
             return false;
         }
    }
    
    public function num_rows( $query )
    {
        self::$counter++;
        $num_rows = $this->link->query( $query );
        if( $this->link->error )
        {
            $this->log_db_errors( $this->link->error, $query );
            return $this->link->error;
        }
        else
        {
            return $num_rows->num_rows;
        }
    }    
   
    public function exists( $table = '', $check_val = '', $params = array() )
    {
        self::$counter++;
        if( empty($table) || empty($check_val) || empty($params) )
        {
            return false;
        }
        $check = array();
        foreach( $params as $field => $value )
        {
            if( !empty( $field ) && !empty( $value ) )
            {
                if( $this->db_common( $value ) )
                {
                    $check[] = "$field = $value";   
                }
                else
                {
                    $check[] = "$field = '$value'";   
                }
            }

        }
        $check = implode(' AND ', $check);

        $rs_check = "SELECT $check_val FROM ".$table." WHERE $check";
        $number = $this->num_rows( $rs_check );
        if( $number === 0 )
        {
            return false;
        }
        else
        {
            return true;
        }
    }
	
    public function get_row( $query, $object = false )
    {
        self::$counter++;
        $row = $this->link->query( $query );
        if( $this->link->error )
        {
            $this->log_db_errors( $this->link->error, $query );
            return false;
        }
        else
        {
            $r = ( !$object ) ? $row->fetch_assoc() : $row->fetch_object();
            return $r;   
        }
    }
	
    public function get_results( $query, $object = false )
    {
        self::$counter++;
        $row = null;
        
        $results = $this->link->query( $query );
        if( $this->link->error )
        {
            $this->log_db_errors( $this->link->error, $query );
            return false;
        }
        else
        {
            $row = array();
            while( $r = ( !$object ) ? $results->fetch_assoc() : $results->fetch_object() )
            {
                $row[] = $r;
            }
            return $row;   
        }
    }
	
    public function insert( $table, $variables = array() )
    {
        self::$counter++;
        if( empty( $variables ) )
        {
            return false;
        }
        
        $sql = "INSERT INTO ". $table;
        $fields = array();
        $values = array();
        foreach( $variables as $field => $value )
        {
            $fields[] = $field;
            $values[] = "'".$value."'";
        }
        $fields = ' (' . implode(', ', $fields) . ')';
        $values = '('. implode(', ', $values) .')';
        
        $sql .= $fields .' VALUES '. $values;

        $query = $this->link->query( $sql );
        
        if( $this->link->error )
        {
            $this->log_db_errors( $this->link->error, $sql );
            return false;
        }
        else
        {
            return true;
        }
    }
	
    public function insert_safe( $table, $variables = array() )
    {
        self::$counter++;
        if( empty( $variables ) )
        {
            return false;
        }
        
        $sql = "INSERT INTO ". $table;
        $fields = array();
        $values = array();
        foreach( $variables as $field => $value )
        {
            $fields[] = $this->filter( $field );
            $values[] = $value; 
        }
        $fields = ' (' . implode(', ', $fields) . ')';
        $values = '('. implode(', ', $values) .')';
        
        $sql .= $fields .' VALUES '. $values;
        $query = $this->link->query( $sql );
        
        if( $this->link->error )
        {
            $this->log_db_errors( $this->link->error, $sql );
            return false;
        }
        else
        {
            return true;
        }
    }
	
    public function insert_multi( $table, $columns = array(), $records = array() )
    {

        self::$counter++;
		
        if( empty( $columns ) || empty( $records ) )
        {
            return false;
        }

        $number_columns = count( $columns );

        $added = 0;

        $sql = "INSERT INTO ". $table;

        $fields = array();
        foreach( $columns as $field )
        {
            $fields[] = '`'.$field.'`';
        }
        $fields = ' (' . implode(', ', $fields) . ')';
        
        $values = array();
        foreach( $records as $record )
        {
            if( count( $record ) == $number_columns )
            {
                $values[] = '(\''. implode( '\', \'', array_values( $record ) ) .'\')';
                $added++;
            }
        }
        $values = implode( ', ', $values );

        $sql .= $fields .' VALUES '. $values;

        $query = $this->link->query( $sql );

        if( $this->link->error )
        {
            return $this->log_db_errors( $this->link->error, $sql );
            return false;
            
        }
        else
        {
            return $added;
        }
    }
	
    public function update( $table, $variables = array(), $where = array(), $limit = '' )
    {
        self::$counter++;
        if( empty( $variables ) )
        {
            return false;
        }
        $sql = "UPDATE ". $table ." SET ";
        foreach( $variables as $field => $value )
        {
            
            $updates[] = "`$field` = '$value'";
        }
        $sql .= implode(', ', $updates);
        
        if( !empty( $where ) )
        {
            foreach( $where as $field => $value )
            {
                $value = $value;

                $clause[] = "$field = '$value'";
            }
            $sql .= ' WHERE '. implode(' AND ', $clause);   
        }
        
        if( !empty( $limit ) )
        {
            $sql .= ' LIMIT '. $limit;
        }

        $query = $this->link->query( $sql );

        if( $this->link->error )
        {
            $this->log_db_errors( $this->link->error, $sql );
            return false;
        }
        else
        {
            return true;
        }
    }
	
    public function delete( $table, $where = array(), $limit = '' )
    {
        self::$counter++;
		
        if( empty( $where ) )
        {
            return false;
        }
        
        $sql = "DELETE FROM ". $table;
        foreach( $where as $field => $value )
        {
            $value = $value;
            $clause[] = "$field = '$value'";
        }
        $sql .= " WHERE ". implode(' AND ', $clause);
        
        if( !empty( $limit ) )
        {
            $sql .= " LIMIT ". $limit;
        }
            
        $query = $this->link->query( $sql );

        if( $this->link->error )
        {
            $this->log_db_errors( $this->link->error, $sql );
            return false;
        }
        else
        {
            return true;
        }
    }
	
    public function lastid()
    {
        self::$counter++;
        return $this->link->insert_id;
    }
	
    public function affected()
    {
        return $this->link->affected_rows;
    }
	
    public function num_fields( $query )
    {
        self::$counter++;
        $query = $this->link->query( $query );
        $fields = $query->field_count;
        return $fields;
    }
	
    public function list_fields( $query )
    {
        self::$counter++;
        $query = $this->link->query( $query );
        $listed_fields = $query->fetch_fields();
        return $listed_fields;
    }
	
    public function truncate( $tables = array() )
    {
        if( !empty( $tables ) )
        {
            $truncated = 0;
            foreach( $tables as $table )
            {
                $truncate = "TRUNCATE TABLE `".trim($table)."`";
                $this->link->query( $truncate );
                if( !$this->link->error )
                {
                    $truncated++;
                    self::$counter++;
                }
            }
            return $truncated;
        }
    }
	
    public function display( $variable, $echo = true )
    {
        $out = '';
        if( !is_array( $variable ) )
        {
            $out .= $variable;
        }
        else
        {
            $out .= '<pre>';
            $out .= print_r( $variable, TRUE );
            $out .= '</pre>';
        }
        if( $echo === true )
        {
            echo $out;
        }
        else
        {
            return $out;
        }
    }
	
    public function total_queries()
    {
        return self::$counter;
    }
	
    static function getInstance()
    {
        if( self::$inst == null )
        {
            self::$inst = new DB();
        }
        return self::$inst;
    }
    
    public function disconnect()
    {
        $this->link->close();
    }

    public function generatePassword($length=16,$level=2){
       list($usec, $sec) = explode(' ', microtime());
       srand((float) $sec + ((float) $usec * 100000));
       $validchars[1] = "0123456789abcdfghjkmnpqrstvwxyz";
       $validchars[2] = "0123456789abcdfghjkmnpqrstvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
       $validchars[3] = "0123456789_!@#$%&*()-=+/abcdfghjkmnpqrstvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_!@#$%&*()-=+/";
       $validchars[4] = "0123456789abcdfghjkmnpqrstvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%*";
       $validchars[5] = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
       //$validchars[6] = "0123456789";
       $validchars[6] = "123456789";
       $password  = "";
       $counter   = 0;
       while ($counter < $length) {
         $actChar = substr($validchars[$level], rand(0, strlen($validchars[$level])-1), 1);
         if (!strstr($password, $actChar)) {
            $password .= $actChar;
            $counter++;
         }
       }
       return $password;
    }


}