import requests

url = "http://127.0.0.1:5000/predict"

# Example of a likely REJECTED applicant
data = {
    "name": "Bob Risky",
    "annual_income": 12000,             # very low income
    "debt_to_income_ratio": 0.8,        # very high debt
    "credit_score": 450,                # poor credit score
    "loan_amount": 25000,               # large loan relative to income
    "interest_rate": 12.0,              # high interest
    "gender": "Male",
    "marital_status": "Single",
    "education_level": "Highschool",
    "employment_status": "Unemployed",
    "loan_purpose": "Personal",
    "grade_subgrade": "C2"
}

response = requests.post(url, json=data)
print(response.json())
