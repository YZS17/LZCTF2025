FROM python:3.8-slim-buster

WORKDIR /

COPY src/requirements.txt requirements.txt
RUN pip3 install -r requirements.txt

COPY src/ .
COPY start.sh .

RUN chmod +x start.sh

EXPOSE 80

CMD [ "./start.sh" ]
