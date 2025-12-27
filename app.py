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

try:
    model = joblib.load(BASE_DIR / "ml_model" / "model.pkl")
    scaler = joblib.load(BASE_DIR / "ml_model" / "scaler.pkl")
    label_encoders = joblib.load(BASE_DIR / "ml_model" / "label_encoders.pkl")
    feature_names = joblib.load(BASE_DIR / "ml_model" / "feature_names.pkl")
except Exception as e:
    print(f"Error loading model or preprocessing files: {e}")
    exit(1)

# =====================================================
# FLASK APP
# =====================================================
app = Flask(__name__)
CORS(app)  # Allow requests from any origin, useful for PHP running on localhost

# =====================================================
# HELPER FUNCTION
# =====================================================
def preprocess_input(data):
    """Convert input JSON to DataFrame and preprocess for prediction"""
    test_data = pd.DataFrame([data])

    # Ensure numeric columns
    numeric_cols = ["annual_income", "debt_to_income_ratio", "credit_score", "loan_amount", "interest_rate"]
    for col in numeric_cols:
        test_data[col] = pd.to_numeric(test_data[col], errors="coerce").fillna(0)

    # Encode categorical columns
    categorical_features = ["gender", "marital_status", "education_level", "employment_status", "loan_purpose", "grade_subgrade"]
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
                test_data[col] = le.transform([le.classes_[0]])  # default to first class if unknown

    # Ensure correct feature order
    test_data = test_data[feature_names]

    # Scale numeric features
    test_data_scaled = scaler.transform(test_data)
    return test_data_scaled

# =====================================================
# PREDICTION ENDPOINT
# =====================================================
@app.route("/predict", methods=["POST"])
def predict():
    try:
        data = request.get_json()
        if not data:
            return jsonify({"error": "No input data received"}), 400

        # Preprocess input
        X = preprocess_input(data)

        # Prediction
        prediction = model.predict(X)[0]
        proba = model.predict_proba(X)[0]

        return jsonify({
            "prediction": "APPROVED" if prediction == 1 else "REJECTED",
            "approval_probability": round(float(proba[1]), 4),
            "rejection_probability": round(float(proba[0]), 4)
        })

    except Exception as e:
        # Always return JSON error
        return jsonify({"error": f"Server error: {str(e)}"}), 500

# =====================================================
# RUN SERVER
# =====================================================
if __name__ == "__main__":
    print("Starting Flask ML API on http://127.0.0.1:5000")
    app.run(host="127.0.0.1", port=5000, debug=True)
