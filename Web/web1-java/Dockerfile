FROM maven:3.8-openjdk-11 AS build

# Copy source code and support files
COPY ./src /app/src
COPY ./pom.xml /app/pom.xml

# Build the application
WORKDIR /app
RUN mvn clean package -DskipTests

# Create the runtime image
FROM openjdk:11-jre-slim

# Install required packages
RUN apt-get update && \
    apt-get install -y gcc && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# Copy files to image
WORKDIR /
COPY --from=build /app/target/serialies-0.0.1-SNAPSHOT.jar /app.jar
COPY ./readflag.c /readflag.c
COPY ./setup.sh /setup.sh
RUN chmod +x /setup.sh

# Compile readflag binary
RUN gcc -o /readflag /readflag.c && \
    rm /readflag.c

# Create a non-root user for running the application
RUN adduser --disabled-password --gecos "" ctf
USER root

# Set environment variables
ENV JAVA_OPTS=""
ENV SERVER_PORT=80

# Expose the application port
EXPOSE 80

# Run setup script to set up flag and start application
CMD ["/setup.sh"]