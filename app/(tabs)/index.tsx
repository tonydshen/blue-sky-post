import AsyncStorage from "@react-native-async-storage/async-storage";
import * as DocumentPicker from "expo-document-picker";
import * as ImagePicker from "expo-image-picker";
import React, { useEffect, useState } from "react";
import {
  ActivityIndicator,
  Alert,
  Image,
  KeyboardAvoidingView,
  Linking,
  Platform,
  ScrollView,
  StyleSheet,
  Switch,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from "react-native";

// --- Types ---
interface PostMedia {
  uri: string;
  type: "image" | "video";
  caption: string;
  name: string;
}

interface DocumentItem {
  uri: string;
  caption: string;
  name: string;
  mimeType?: string;
}

interface UserProfile {
  fname: string;
  lname: string;
  phone: string;
  email: string;
  weixin: string;
  address: string;
}

export default function BlueSkyApp() {
  const [currentTab, setCurrentTab] = useState("Home");
  const [postTitle, setPostTitle] = useState("");
  const [postDescription, setPostDescription] = useState("");
  const [mediaList, setMediaList] = useState<PostMedia[]>([]);
  const [documentList, setDocumentList] = useState<DocumentItem[]>([]);
  const [uploading, setUploading] = useState(false);
  const [locationEnabled, setLocationEnabled] = useState(true);

  const [profile, setProfile] = useState<UserProfile>({
    fname: "",
    lname: "",
    phone: "",
    email: "",
    weixin: "",
    address: "",
  });

  // Load Profile on startup
  useEffect(() => {
    const loadData = async () => {
      const savedProfile = await AsyncStorage.getItem("@user_profile");
      const savedSettings = await AsyncStorage.getItem("@location_setting");
      if (savedProfile) setProfile(JSON.parse(savedProfile));
      if (savedSettings) setLocationEnabled(JSON.parse(savedSettings));
    };
    loadData();
  }, []);

  const saveProfile = async (newProfile: UserProfile) => {
    setProfile(newProfile);
    await AsyncStorage.setItem("@user_profile", JSON.stringify(newProfile));
  };

  const toggleLocation = async (val: boolean) => {
    setLocationEnabled(val);
    await AsyncStorage.setItem("@location_setting", JSON.stringify(val));
  };

  // --- Core Functions ---
  const pickMedia = async () => {
    let result = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ImagePicker.MediaTypeOptions.All,
      allowsMultipleSelection: true,
      quality: 0.8,
    });
    if (!result.canceled) {
      const newItems: PostMedia[] = result.assets.map((asset) => ({
        uri: asset.uri,
        type: asset.type === "video" ? "video" : "image",
        caption: "",
        name: asset.uri.split("/").pop() || "upload.jpg",
      }));
      setMediaList([...mediaList, ...newItems]);
    }
  };

  const pickDocument = async () => {
    try {
      // First attempt: Try with common document MIME types
      let result = await DocumentPicker.getDocumentAsync({
        type: [
          "application/pdf",
          "application/msword",
          "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
          "application/vnd.ms-excel",
          "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
          "text/plain",
          "text/csv",
        ],
        copyToCacheDirectory: true,
      });

      // Fallback: If first attempt fails or is canceled, try with wildcard
      if (result.canceled) {
        console.warn("Document picker canceled, trying with wildcard type...");
        result = await DocumentPicker.getDocumentAsync({
          type: "*/*",
          copyToCacheDirectory: true,
        });
      }

      if (!result.canceled && result.assets && result.assets.length > 0) {
        const asset = result.assets[0];
        console.log("Document picked:", asset.name, asset.mimeType);
        
        const newDocument: DocumentItem = {
          uri: asset.uri,
          caption: "",
          name: asset.name || "document",
          mimeType: asset.mimeType || "application/octet-stream",
        };
        setDocumentList([...documentList, newDocument]);
      } else {
        console.log("Document picker was canceled or no assets returned");
      }
    } catch (err: any) {
      console.error("Document picker error:", err);
      Alert.alert(
        "Error",
        `Failed to pick document: ${err.message || "Unknown error"}`
      );
    }
  };

  const removeDocument = (index: number) => {
    const updated = [...documentList];
    updated.splice(index, 1);
    setDocumentList(updated);
  };

  // Alternative document picker - uses no MIME type restrictions (broadest compatibility)
  const pickDocumentUniversal = async () => {
    try {
      console.log("Launching universal document picker...");
      const result = await DocumentPicker.getDocumentAsync({
        type: "*/*",
        copyToCacheDirectory: true,
      });

      if (!result.canceled && result.assets && result.assets.length > 0) {
        const asset = result.assets[0];
        console.log("Document picked (universal):", {
          name: asset.name,
          mimeType: asset.mimeType,
          size: asset.size,
        });

        const newDocument: DocumentItem = {
          uri: asset.uri,
          caption: "",
          name: asset.name || "document",
          mimeType: asset.mimeType || "application/octet-stream",
        };
        setDocumentList([...documentList, newDocument]);
      }
    } catch (err: any) {
      console.error("Universal document picker error:", err);
      Alert.alert("Error", `Failed to pick document: ${err.message || "Unknown error"}`);
    }
  };

  const uploadToDCL = async () => {
    if (mediaList.length === 0 && documentList.length === 0) return;
    setUploading(true);

    try {
      const formData = new FormData();
      formData.append("action", "upload");

      // Append Post Title and Description
      formData.append("post_title", postTitle);
      formData.append("post_description", postDescription);

      // Append Profile
      Object.keys(profile).forEach((key) => {
        formData.append(key, profile[key as keyof UserProfile]);
      });
      formData.append("use_location", locationEnabled ? "1" : "0");

      mediaList.forEach((item, index) => {
        const file = {
          uri: item.uri,
          type: item.type === "video" ? "video/mp4" : "image/jpeg",
          name: item.name,
        } as any;
        formData.append(`file_${index}`, file);
        formData.append(`caption_${index}`, item.caption);
      });

      // Append documents with captions
      documentList.forEach((doc, index) => {
        const file = {
          uri: doc.uri,
          type: doc.mimeType || "application/octet-stream",
          name: doc.name,
        } as any;
        formData.append(`document_${index}`, file);
        formData.append(`document_caption_${index}`, doc.caption);
      });

      const response = await fetch(
        "https://datacommlab.com/scripts/test3.php",
        {
          method: "POST",
          body: formData,
          headers: {
            "Content-Type": "multipart/form-data",
            "X-Requested-With": "XMLHttpRequest",
          },
        },
      );

      const result = await response.json();
      if (result.ok) {
        // SUCCESS ALERT WITH ACTION BUTTON
        Alert.alert("Post Live!", "Your post is ready to view.", [
          { text: "View Post", onPress: () => Linking.openURL(result.url) },
          { text: "Done", style: "cancel" },
        ]);
        setMediaList([]);
        setDocumentList([]);
        setPostTitle("");
        setPostDescription("");
      } else {
        throw new Error(result.error);
      }
    } catch (e: any) {
      Alert.alert("Upload Failed", e.message);
    } finally {
      setUploading(false);
    }
  };

  // --- Sub-Views ---
  const renderHome = () => (
    <ScrollView style={styles.content}>
      {/* Post Title and Description */}
      <View style={styles.postHeader}>
        <TextInput
          style={styles.titleInput}
          placeholder="Enter post title..."
          value={postTitle}
          onChangeText={setPostTitle}
          maxLength={100}
        />
        <TextInput
          style={styles.descriptionInput}
          placeholder="Enter post description..."
          value={postDescription}
          onChangeText={setPostDescription}
          multiline
          maxLength={500}
        />
      </View>

      {mediaList.map((item, index) => (
        <View key={index} style={styles.mediaCard}>
          <Image source={{ uri: item.uri }} style={styles.preview} />
          <TextInput
            style={styles.input}
            placeholder="Add caption..."
            value={item.caption}
            onChangeText={(t) => {
              const m = [...mediaList];
              m[index].caption = t;
              setMediaList(m);
            }}
          />
        </View>
      ))}
      <TouchableOpacity style={styles.btnRed} onPress={pickMedia}>
        <Text style={styles.btnText}>+ Add Photo/Video</Text>
      </TouchableOpacity>

      {/* Documents Section */}
      {documentList.map((doc, index) => (
        <View key={index} style={styles.mediaCard}>
          <View style={styles.documentPreview}>
            <Text style={styles.documentIcon}>📄</Text>
            <Text style={styles.documentFileName} numberOfLines={1}>
              {doc.name}
            </Text>
          </View>
          <TextInput
            style={styles.input}
            placeholder="Add document name, title, author, or description (optional)..."
            value={doc.caption}
            onChangeText={(t) => {
              const d = [...documentList];
              d[index].caption = t;
              setDocumentList(d);
            }}
          />
          <TouchableOpacity
            style={styles.removeDocBtn}
            onPress={() => removeDocument(index)}
          >
            <Text style={styles.removeBtnText}>Remove Document</Text>
          </TouchableOpacity>
        </View>
      ))}

      <TouchableOpacity style={styles.btnOrange} onPress={pickDocumentUniversal}>
        <Text style={styles.btnText}>+ Add Document (PDF, Word, etc.)</Text>
      </TouchableOpacity>

      {(mediaList.length > 0 || documentList.length > 0) && (
        <TouchableOpacity
          style={styles.btnBlue}
          onPress={uploadToDCL}
          disabled={uploading}
        >
          {uploading ? (
            <ActivityIndicator color="#fff" />
          ) : (
            <Text style={styles.btnText}>Post to DCL Server</Text>
          )}
        </TouchableOpacity>
      )}
    </ScrollView>
  );

  const renderProfile = () => (
    <ScrollView
      style={styles.content}
      contentContainerStyle={{ paddingBottom: 300 }}
      keyboardShouldPersistTaps="handled"
    >
      {/* Table-like rows */}
      <View style={styles.profileRow}>
        <Text style={styles.compactLabel}>First Name:</Text>
        <TextInput
          style={styles.compactInput}
          value={profile.fname}
          onChangeText={(t) => saveProfile({ ...profile, fname: t })}
        />
      </View>

      <View style={styles.profileRow}>
        <Text style={styles.compactLabel}>Last Name:</Text>
        <TextInput
          style={styles.compactInput}
          value={profile.lname}
          onChangeText={(t) => saveProfile({ ...profile, lname: t })}
        />
      </View>

      <View style={styles.profileRow}>
        <Text style={styles.compactLabel}>Mobile:</Text>
        <TextInput
          style={styles.compactInput}
          keyboardType="phone-pad"
          value={profile.phone}
          onChangeText={(t) => saveProfile({ ...profile, phone: t })}
        />
      </View>

      <View style={styles.profileRow}>
        <Text style={styles.compactLabel}>Email:</Text>
        <TextInput
          style={styles.compactInput}
          keyboardType="email-address"
          value={profile.email}
          onChangeText={(t) => saveProfile({ ...profile, email: t })}
        />
      </View>

      <View style={styles.profileRow}>
        <Text style={styles.compactLabel}>Weixin ID:</Text>
        <TextInput
          style={styles.compactInput}
          value={profile.weixin}
          onChangeText={(t) => saveProfile({ ...profile, weixin: t })}
        />
      </View>

      {/* Address gets a bit more space but keeps the label to the left */}
      <View
        style={[
          styles.profileRow,
          { alignItems: "flex-start", paddingTop: 10 },
        ]}
      >
        <Text style={styles.compactLabel}>Address:</Text>
        <TextInput
          style={[
            styles.compactInput,
            { height: 60, textAlignVertical: "top" },
          ]}
          multiline
          value={profile.address}
          onChangeText={(t) => saveProfile({ ...profile, address: t })}
        />
      </View>

      {/* Big spacer at the bottom allows scrolling the address to the top of the screen */}
      <View style={{ height: 400 }} />
    </ScrollView>
  );

  const renderSettings = () => (
    <View style={styles.content}>
      <View style={styles.row}>
        <Text style={styles.label}>Allow Location Sharing</Text>
        <Switch value={locationEnabled} onValueChange={toggleLocation} />
      </View>
      <Text style={styles.helpText}>
        When enabled, your postcard will show where it was uploaded from.
      </Text>
    </View>
  );

  return (
    <KeyboardAvoidingView
      behavior={Platform.OS === "ios" ? "padding" : undefined}
      style={styles.container}
    >
      {/* 1. Header */}
      <View style={styles.header}>
        <Text
          style={styles.headerText}
          onPress={() => Linking.openURL("https://datacommlab.com")}
        >
          Data Communications Lab -{" "}
          <Text style={{ color: "#007bff" }}>Blue Sky Post</Text>
        </Text>
      </View>

      {/* 2. Content Area */}
      {currentTab === "Home" && renderHome()}
      {currentTab === "Profile" && renderProfile()}
      {currentTab === "Settings" && renderSettings()}
      {currentTab === "Postcards" && (
        <View style={styles.content}>
          <Text>My Postcards (Placeholder)</Text>
        </View>
      )}
      {currentTab === "Accounts" && (
        <View style={styles.content}>
          <Text>My Social Accounts (Placeholder)</Text>
        </View>
      )}

      {/* 3. Footer Navigation */}
      <View style={styles.footer}>
        <Tab
          icon="🖼️"
          label="Home"
          active={currentTab === "Home"}
          onPress={() => setCurrentTab("Home")}
        />
        <Tab
          icon="📜"
          label="Cards"
          active={currentTab === "Postcards"}
          onPress={() => setCurrentTab("Postcards")}
        />
        <Tab
          icon="👤"
          label="Profile"
          active={currentTab === "Profile"}
          onPress={() => setCurrentTab("Profile")}
        />
        <Tab
          icon="🔑"
          label="Accounts"
          active={currentTab === "Accounts"}
          onPress={() => setCurrentTab("Accounts")}
        />
        <Tab
          icon="⚙️"
          label="Settings"
          active={currentTab === "Settings"}
          onPress={() => setCurrentTab("Settings")}
        />
      </View>
    </KeyboardAvoidingView>
  );
}

// --- Components ---
const Tab = ({ icon, label, active, onPress }: any) => (
  <TouchableOpacity
    style={[styles.tab, active && styles.activeTab]}
    onPress={onPress}
  >
    <Text style={{ fontSize: 18 }}>{icon}</Text>
    <Text style={[styles.tabLabel, active && { color: "#007bff" }]}>
      {label}
    </Text>
  </TouchableOpacity>
);

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#fff" },
  header: {
    paddingTop: 50,
    paddingBottom: 15,
    borderBottomWidth: 1,
    borderBottomColor: "#eee",
    alignItems: "center",
  },
  headerText: { fontSize: 16, fontWeight: "bold" },
  footer: {
    flexDirection: "row",
    height: 70,
    borderTopWidth: 1,
    borderTopColor: "#eee",
    backgroundColor: "#f9f9f9",
  },
  tab: { flex: 1, alignItems: "center", justifyContent: "center" },
  activeTab: {
    backgroundColor: "#fff",
    borderTopWidth: 2,
    borderTopColor: "#007bff",
  },
  tabLabel: { fontSize: 10, color: "#666", marginTop: 2 },
  content: { flex: 1, padding: 15 },
  postHeader: {
    marginBottom: 20,
    paddingBottom: 15,
    borderBottomWidth: 1,
    borderBottomColor: "#ddd",
  },
  titleInput: {
    fontSize: 16,
    fontWeight: "bold",
    padding: 10,
    backgroundColor: "#f9f9f9",
    borderRadius: 8,
    marginBottom: 10,
    borderWidth: 1,
    borderColor: "#ddd",
  },
  descriptionInput: {
    fontSize: 14,
    padding: 10,
    backgroundColor: "#f9f9f9",
    borderRadius: 8,
    borderWidth: 1,
    borderColor: "#ddd",
    minHeight: 60,
  },
  mediaCard: {
    marginBottom: 15,
    borderRadius: 10,
    backgroundColor: "#f0f0f0",
    overflow: "hidden",
  },
  preview: { width: "100%", height: 200 },
  input: {
    padding: 12,
    backgroundColor: "#fff",
    borderBottomWidth: 1,
    borderBottomColor: "#ddd",
  },
  label: { fontWeight: "bold", marginTop: 15, marginBottom: 5, color: "#444" },
  btnRed: {
    backgroundColor: "#ff4757",
    padding: 15,
    borderRadius: 10,
    alignItems: "center",
    marginVertical: 10,
  },
  btnOrange: {
    backgroundColor: "#ff9500",
    padding: 15,
    borderRadius: 10,
    alignItems: "center",
    marginVertical: 10,
  },
  btnBlue: {
    backgroundColor: "#007bff",
    padding: 15,
    borderRadius: 10,
    alignItems: "center",
    marginBottom: 30,
  },
  btnText: { color: "#fff", fontWeight: "bold", fontSize: 16 },
  row: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginTop: 20,
  },
  helpText: { fontSize: 12, color: "#888", marginTop: 5 },
  profileRow: {
    flexDirection: "row",
    alignItems: "center",
    paddingVertical: 5,
    borderBottomWidth: 0,
  },
  compactLabel: {
    width: 100,
    fontWeight: "bold",
    color: "#444",
    fontSize: 14,
  },
  compactInput: {
    flex: 1,
    backgroundColor: "#f9f9f9",
    borderRadius: 5,
    padding: 8,
    borderWidth: 1,
    borderColor: "#eee",
    fontSize: 14,
  },
  documentPreview: {
    flexDirection: "row",
    alignItems: "center",
    padding: 15,
    backgroundColor: "#fff9e6",
    borderRadius: 5,
  },
  documentIcon: {
    fontSize: 24,
    marginRight: 10,
  },
  documentFileName: {
    flex: 1,
    fontSize: 13,
    fontWeight: "500",
    color: "#333",
  },
  removeDocBtn: {
    backgroundColor: "#ff6b6b",
    padding: 10,
    borderRadius: 5,
    alignItems: "center",
    marginTop: 8,
  },
});
