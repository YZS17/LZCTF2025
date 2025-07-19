from flask import Flask, request, render_template_string
import html

app = Flask(__name__)

BLACKLIST = [
    'init','globals','builtins','import','os','popen','read','request','application','TemplateReference',
    'cycler','joiner','namespace','lipsum','getitem','config','for','eval','flashed','range','class','mro',
    'subclasses','pyfile','shell','stdout','base','if','module','RUNCMD','format','args','values','form',
    'cookies','headers','pragma','mimetype','origin','referrer','pop','attr','chr','free','palestine','with'
]

BLACKLIST += ['0','1','2','3','4','5','6','7','8','9']

BLACKLIST += ["'",'"',"`",'\\','/','.','_','[',']','{{','}}','#']

@app.route("/", methods=["GET", "POST"])
def home():
    c = request.form.get('c') if request.method == 'POST' else None
    error_message = None
    rendered_template = None
    
    if c:
        c = c.lower()
        for item in BLACKLIST:
            if item in c:
                error_message = "Invalid input detected!"
                break
        else:
            rendered_template = html.unescape(render_template_string(c))
            # can you?
            if "fr3e_p4le$t1ne&!" in rendered_template:
                try:
                    with open('flag.txt', 'r') as flag_file:
                        flag = flag_file.read()
                    return f"Flag: {flag}"
                except FileNotFoundError:
                    return "Flag file not found!"

    return '''
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>大黑阔</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=VT323&display=swap');

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-image: url('/static/image.jpg');
            background-size: cover;
            background-position: center;
            font-family: 'VT323', monospace;
            color: #33FF33;
        }

        .form-container {
            background-color: rgba(0, 0, 0, 0.7);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 15px 5px rgba(0, 255, 0, 0.5);
            max-width: 400px; 
            width: 100%; 
        }

        .form-container input[type="text"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 2px solid #33FF33;
            border-radius: 5px;
            background-color: #000;
            color: #33FF33;
            font-size: 18px;
            box-sizing: border-box; 
        }

        .form-container input[type="submit"] {
            width: 100%;
            padding: 10px;
            background-color: #33FF33;
            border: none;
            border-radius: 5px;
            color: #000;
            font-size: 18px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            box-sizing: border-box; 
        }

        .form-container input[type="submit"]:hover {
            background-color: #00FF00;
        }

        .result, .error {
            margin-top: 20px;
            padding: 10px;
            border: 2px solid #33FF33;
            border-radius: 5px;
            background-color: #000;
            color: #33FF33;
            font-size: 18px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <form method="post">
            Enter template string: <input type="text" name="c">
            <input type="submit" value="Submit">
        </form>
        
        
        <div class="result">{{ rendered_template }}{{ error_message }}</div>
        
    </div>
</body>
</html>
    '''.replace("{{ error_message }}", error_message or "").replace("{{ rendered_template }}", rendered_template or "")
