FROM python:3.11-slim

WORKDIR /app

RUN apt-get update && apt-get install -y \
    libgl1 \
    libglib2.0-0 \
    && rm -rf /var/lib/apt/lists/*

COPY requirements_railway.txt .
COPY requirements.txt .

RUN pip install --upgrade pip

# Install PyTorch CPU from official index first
RUN pip install --no-cache-dir \
    torch==2.2.2+cpu \
    torchvision==0.17.2+cpu \
    --index-url https://download.pytorch.org/whl/cpu

# Install remaining packages
RUN pip install --no-cache-dir -r requirements_railway.txt

COPY . .

ENV PORT=5000

EXPOSE $PORT

CMD gunicorn -b 0.0.0.0:$PORT flask_server:app