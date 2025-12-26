from flask import Flask, request, jsonify
import joblib
import pandas as pd
from pathlib import Path
import warnings
from flask_cors import CORS

# =====================================================
# BASE DIRECTORY
# =====================================================
BASE_DIR = Path(__file__).resolve().parent

# =====================================================
# LOAD MODEL & PREPROCESSING
# =====================================================
warnings.filterwarnings("ignore", message="X does not have valid feature names*")

model = joblib.load(BASE_DIR / "ml_model" / "model.pkl")
scaler = joblib.load(BASE_DIR / "ml_model" / "scaler.pkl")
label_encoders = joblib.load(BASE_DIR / "ml_model" / "label_encoders.pkl")
feature_names = joblib.load(BASE_DIR / "ml_model" / "feature_names.pkl")

# =====================================================
# FLASK APP
# =====================================================
app = Flask(__name__)
CORS(app)

# =====================================================
# PREDICTION ENDPOINT
# =====================================================
@app.route("/predict", methods=["POST"])
def predict():
    data = request.get_json()

    if not data:
        return jsonify({"error": "No input data received"}), 400

    # Convert to DataFrame
    test_data = pd.DataFrame([data])

    # Ensure numeric columns
    numeric_cols = [
        "annual_income",
        "debt_to_income_ratio",
        "credit_score",
        "loan_amount",
        "interest_rate"
    ]

    for col in numeric_cols:
        test_data[col] = pd.to_numeric(test_data[col], errors="coerce").fillna(0)

    # Encode categorical features
    categorical_features = [
        "gender",
        "marital_status",
        "education_level",
        "employment_status",
        "loan_purpose",
        "grade_subgrade"
    ]

    for col in categorical_features:
        if col in label_encoders:
            le = label_encoders[col]
            value = test_data.at[0, col]

            if col == "grade_subgrade":
                value = str(value).upper()
            else:
                value = str(value).title()

            if value in le.classes_:
                test_data[col] = le.transform([value])
            else:
                test_data[col] = le.transform([le.classes_[0]])

    # Ensure correct feature order
    test_data = test_data[feature_names]

    # Scale
    test_data_scaled = scaler.transform(test_data)

    # Predict
    prediction = model.predict(test_data_scaled)[0]
    proba = model.predict_proba(test_data_scaled)[0]

    return jsonify({
        "prediction": "APPROVED" if prediction == 1 else "REJECTED",
        "approval_probability": round(float(proba[1]), 4),
        "rejection_probability": round(float(proba[0]), 4)
    })

# =====================================================
# RUN SERVER
# =====================================================
if __name__ == "__main__":
    app.run(host="127.0.0.1", port=5000, debug=True)
