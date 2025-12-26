import requests

url = "http://127.0.0.1:5000/predict"
data = {
    "annual_income": 50000,
    "debt_to_income_ratio": 0.25,
    "credit_score": 720,
    "loan_amount": 15000,
    "interest_rate": 5.5,
    "gender": "Male",
    "marital_status": "Single",
    "education_level": "Bachelor",
    "employment_status": "Employed",
    "loan_purpose": "Car",
    "grade_subgrade": "A1"
}

response = requests.post(url, json=data)
print(response.json())
