import pandas as pd
import joblib
from pathlib import Path
import warnings

# =====================================================
# BASE DIRECTORY
# =====================================================
BASE_DIR = Path(__file__).resolve().parent

# =====================================================
# LOAD MODEL AND PREPROCESSING OBJECTS
# =====================================================
warnings.filterwarnings("ignore", message="X does not have valid feature names*")

try:
    model = joblib.load(BASE_DIR / "model.pkl")
    scaler = joblib.load(BASE_DIR / "scaler.pkl")
    label_encoders = joblib.load(BASE_DIR / "label_encoders.pkl")
    feature_names = joblib.load(BASE_DIR / "feature_names.pkl")
    print("✅ Model and objects loaded successfully")
except Exception as e:
    print(f"❌ Error loading files: {e}")
    exit()

# =====================================================
# PREDICTION FUNCTION
# =====================================================
def predict_loan_approval(
    annual_income,
    debt_to_income_ratio,
    credit_score,
    loan_amount,
    interest_rate,
    gender,
    marital_status,
    education_level,
    employment_status,
    loan_purpose,
    grade_subgrade
):
    # -----------------------------
    # Create DataFrame
    # -----------------------------
    test_data = pd.DataFrame({
        'annual_income': [annual_income],
        'debt_to_income_ratio': [debt_to_income_ratio],
        'credit_score': [credit_score],
        'loan_amount': [loan_amount],
        'interest_rate': [interest_rate],
        'gender': [gender.title()],
        'marital_status': [marital_status.title()],
        'education_level': [education_level.title()],
        'employment_status': [employment_status.title()],
        'loan_purpose': [loan_purpose.title()],
        'grade_subgrade': [grade_subgrade.upper()]
    })

    # -----------------------------
    # Encode categorical features
    # -----------------------------
    categorical_features = ['gender','marital_status','education_level','employment_status','loan_purpose','grade_subgrade']
    for col in categorical_features:
        if col in label_encoders:
            le = label_encoders[col]
            value = test_data.at[0,col]
            if value in le.classes_:
                test_data[col] = le.transform([value])
            else:
                test_data[col] = le.transform([le.classes_[0]])

    # -----------------------------
    # Ensure correct order
    # -----------------------------
    test_data = test_data[feature_names]

    # -----------------------------
    # Scale features
    # -----------------------------
    test_data_scaled = scaler.transform(test_data)

    # -----------------------------
    # Predict
    # -----------------------------
    prediction = model.predict(test_data_scaled)[0]
    proba = model.predict_proba(test_data_scaled)[0]

    return {
        "prediction": "APPROVED ✅" if prediction==1 else "REJECTED ❌",
        "approval_probability": float(proba[1]),
        "rejection_probability": float(proba[0])
    }
