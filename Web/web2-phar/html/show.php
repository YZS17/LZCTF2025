<?php
class sing{
    public $apple;
    public $range;
    public function __destruct()
    {
        if($this->range == "range"){
            echo "apple is ?".$this->apple;
        }
    }
}

class song{
    public $banana;
    public $abble;
    public function __toString()
    {
        if($this->abble == "abble"){
            return $this->banana->ernb();
        }
    }
}

class rap{
    public $text;
    public function __call($name, $arguments)
    {
        return $this->text->aaabbb;
    }
}

class basketball{
    public $payload;
    public function __get($name)
    {
        if(!preg_match("/flag|system|php|cat|eval|tac|sort|shell|%|~|\\^|\\.|\'/i", $this->payload)){
            @eval($this->payload);
        }
    }
}


if (isset($_GET['file'])) {
    $imagePath = $_GET['file'];
    if (preg_match("/(\/flag|\/fl|\/f|sort)/i", $imagePath)){
    exit();
    }
    $imageData = file_get_contents($imagePath);

    if ($imageData !== false) {

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_buffer($finfo, $imageData);
        finfo_close($finfo);

        header("Content-Type: $mimeType");
        
        echo $imageData;
        exit;
    } else {
        echo "Image cannot be read.";
    }
}
?>
