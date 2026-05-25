FROM php:8.1-cli

# Install dependencies required by the library, including Python for logchecker checksum validation
RUN apt-get update && apt-get install -y \
    unzip \
    libzip-dev \
    git \
    python3 \
    python3-pip \
    python3-venv \
    && docker-php-ext-install zip

# Install Python optional requirements for Logchecker (eac/xld logcheckers and chardet)
# We create a virtual environment to avoid PEP 668 PEP-BREAK-SYSTEM-PACKAGES errors on newer Debian
RUN python3 -m venv /opt/venv
ENV PATH="/opt/venv/bin:$PATH"
RUN pip3 install --no-cache-dir chardet eac-logchecker xld-logchecker

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set up a new user named "user" with user ID 1000
# Hugging Face Spaces require containers to run as a non-root user (UID 1000)
RUN useradd -m -u 1000 user
USER user

# Set home to the user's home directory
ENV HOME=/home/user \
    PATH=/home/user/.local/bin:/opt/venv/bin:$PATH

# Set the working directory to the user's home directory
WORKDIR $HOME/app

# Copy the application files and assign ownership to the user
COPY --chown=user . $HOME/app

# Install composer dependencies
RUN composer install --no-dev --optimize-autoloader

# Expose the port Hugging Face requires
EXPOSE 7860

# Start the PHP built-in web server on port 7860
CMD ["php", "-S", "0.0.0.0:7860", "-t", "public"]
